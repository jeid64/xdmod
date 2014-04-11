<?php

@session_start();
@set_time_limit(0);

@require_once dirname(__FILE__).'/../../../configuration/linker.php';

try {
    $logged_in_user = \xd_security\detectUser(array(XDUser::PUBLIC_USER));

    $debug_level = 0;

    if (isset($_REQUEST['debug_level'])) {
        $debug_level = abs(intval($_REQUEST['debug_level']));
    }

    $query_group = 'tg_summary';

    if (isset($_REQUEST['query_group'])) {
        $query_group = $_REQUEST['query_group'];
    }

    $activeRole = $logged_in_user->getMostPrivilegedRole();

    if (!isset($_REQUEST['start_date'])) {
        throw new Exception("start_date parameter is not set");
    }

    if (!isset($_REQUEST['end_date'])) {
        throw new Exception("end_date parameter is not set");
    }

    $start_date = $_REQUEST['start_date'];
    $end_date = $_REQUEST['end_date'];

    $aggregation_unit = 'auto';

    if (isset($_REQUEST['aggregation_unit'])) {
        $aggregation_unit = lcfirst($_REQUEST['aggregation_unit']);
    }

    $rp_summary_regex = '/rp_(?P<rp_id>[0-9]+)_summary/';

    $charts_query_group = 'tg_summary';

    $raw_parameters = array();
    if (   $query_group === 'my_usage' || $query_group === 'my_summary'
        || $query_group === 'tg_usage' || $query_group === 'tg_summary'
    ) {
        //
    } elseif (preg_match($rp_summary_regex, $query_group, $matches) > 0) {
        $raw_parameters['provider'] = $matches['rp_id'];
    } else {
        //$charts_query_group = 'my_summary';

        $role_data = explode(':', substr($query_group, 0, strpos($query_group, '_summary')));
        $role_data = array_pad($role_data, 2, NULL);

        $activeRole = $logged_in_user->assumeActiveRole($role_data[0], $role_data[1]);

        $raw_parameters = $activeRole->getParameters();
    }

    $query_descripter = new \User\Elements\QueryDescripter($charts_query_group, 'Jobs', 'none');

    $query = new \DataWarehouse\Query\Jobs\Aggregate($aggregation_unit, $start_date, $end_date, 'none', 'all', $query_descripter->pullQueryParameters($raw_parameters));

    $result = $query ->execute();

    $summaryCharts = $activeRole->getSummaryCharts();

    foreach ($summaryCharts as $i => $summaryChart) {
        $summaryChartObject = json_decode($summaryChart);
        $summaryChartObject->preset = true;
        $summaryCharts[$i] = json_encode($summaryChartObject);
    }

    if (!isset($_REQUEST['public_user']) || $_REQUEST['public_user'] != 'true')
    {
        $userProfile = $logged_in_user->getProfile();

        $queries = $userProfile->fetchValue('queries');

        if ($queries != NULL) {
            $queriesArray = array_values(json_decode($queries, true));

            foreach ($queriesArray as $i => $query) {
                $queryConfig = json_decode($query['config']);

                //if (!isset($queryConfig->summary_index))
                //{

                if (preg_match('/summary_(?P<index>\S+)/', $query['name'], $matches) > 0) {
                    $queryConfig->summary_index = $matches['index'];
                } else {
                    $queryConfig->summary_index = $query['name'];
                }

                //}

                if (isset($queryConfig->featured) && $queryConfig->featured) {
                    //echo 'xxx'.$queryConfig->summary_index;
                    if (isset($summaryCharts[$queryConfig->summary_index])) {
                        $queryConfig->preset = true;
                    }
                    $summaryCharts[$queryConfig->summary_index] = json_encode($queryConfig);
                }
            }
        }
    }

    foreach ($summaryCharts as $i => $summaryChart) {
        $summaryChartObject = json_decode($summaryChart);
        $summaryChartObject->index = $i;
        $summaryCharts[$i] = json_encode($summaryChartObject);
    }
    ksort($summaryCharts, SORT_STRING);
    //print_r($summaryCharts);
    $result['charts'] = json_encode(array_values($summaryCharts));

    echo json_encode(array('totalCount' => 1, 'success' => true, 'message' => '', 'data' => array($result) ));

} catch (Exception $ex) {
    echo json_encode(array('totalCount' => 0,
                           'message' => $ex->getMessage()."<hr>".$ex->getTraceAsString(),
                           'data' => array(),
                           'success' => false));
}

