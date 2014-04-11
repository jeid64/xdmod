<?php

namespace User;

use CCR\DB;
use Xdmod\Config;

/*
* Abstract 'factory' class for mapping role-based information to a particular user.
* The XDUser class will rely on this class for ultimately determining what data is available to
* any given user.
*
*/

abstract class aRole implements \User\iRole
{
    // @string (assigned in call to constructor of target role extending this class)
    private $_identifier;

    private $_user_id;

    protected $_simulated_organization = '';

    private $_params = array();
    private $_dashboardContents = array();
    private $_permittedModules = array();

    private $_querys = array(); //maps querygroup_name to Query object

    protected $_roleCategories = array();

    private static $_config;

    // ------------------------------------
    // All classes which extend aRole will make a call to this constructor, passing in an identifier
    // (all of which are defined in configuration/constants.php)

    protected function __construct($identifier)
    {

        $this->_identifier = $identifier;
        $this->_user_id = NULL;                    // <-- this variable will be assigned using the call to 'configure()...'

        $this->_roleCategories = array('tg' => ORGANIZATION_NAME);

        $modules = self::getConfig($this->_identifier, 'permitted_modules');
        foreach ($modules as $moduleConfig) {
            $default = isset($moduleConfig['default']) && $moduleConfig['default'];

            $module = new \User\Elements\Module(
                $moduleConfig['name'],
                $default,
                $moduleConfig['title']
            );

            $this->addPermittedModule($module);
        }

        $descripters = self::getConfig($this->_identifier, 'query_descripters');
        foreach ($descripters as $descripterConfig) {
            $descripter = new \User\Elements\QueryDescripter(
                'tg_usage',
                $descripterConfig['realm'],
                $descripterConfig['group_by']
            );

            if (isset($descripterConfig['show'])) {
                $descripter->setShowMenu($descripterConfig['show']);
            }

            if (isset($descripterConfig['disable'])) {
                $descripter->setDisableMenu($descripterConfig['disable']);
            }

            $this->addQueryDescripter($descripter);
        }

    }//__construct

    // ------------------------------------
    // configure: Generates the parameters associated with a user and the role mapped to that user.  Access to the
    //                parameters is accomplished by calling getParameters()
    // @param XDUser $user
    // @param [optional] int $simulatedActiveRole (if supplied, configure(...) will not check for an active flag; instead it will directly
    //                                                            consult the role referenced by $simulatedActiveRole)

    public function configure(\XDUser $user, $simulatedActiveRole = NULL)
    {

        $this->_params = array();

        $this->_user_id = $user->getUserID();

        if ($simulatedActiveRole != NULL) {

            $this->_simulated_organization = $simulatedActiveRole;

            $query = "SELECT p.param_name, p.param_op, p.param_value " .
                        "FROM UserRoleParameters AS p, Roles AS r " .
                        "WHERE user_id={$user->getUserID()} AND r.abbrev='{$this->getIdentifier()}' AND r.role_id = p.role_id AND p.param_value='$simulatedActiveRole'";

        }
        else {

            $query = "SELECT p.param_name, p.param_op, p.param_value " .
                        "FROM UserRoleParameters AS p, Roles AS r " .
                        "WHERE user_id={$user->getUserID()} AND r.abbrev='{$this->getIdentifier()}' AND r.role_id = p.role_id AND p.is_active=1";

        }

        $dbh = DB::factory('database');

        $results = $dbh->query($query);

        foreach($results as $result) {

            // $p = new \DataWarehouse\Query\Model\Parameter($result['param_name'], $result['param_op'], $result['param_value']);

            $this->addParameter($result['param_name'], $result['param_value']);

        }//foreach

    }//configure

    // ------------------------------------

    public function getCorrespondingUserID()
    {
        if ($this->_user_id == NULL) {

            // Common case:  A new user has been created, yet not saved.

            throw new \Exception('No user ID has been assigned to this role.  You must call configure() before calling getCorrespondingUserID()');
        }

        return $this->_user_id;

    }//getCorrespondingUserID

    // ------------------------------------

    public function getParameters()
    {
        return $this->_params;

    }//getParameters

    // ------------------------------------

    protected function addParameter($param_name, $param_value)
    {
        $this->_params[$param_name] = $param_value;

    }//addParameter

    // ------------------------------------

    public function getDashboardContents()
    {
        return $this->_dashboardContent;

    }//getDashboardContents

    // ------------------------------------

    protected function addDashboardContent(\User\Elements\DashboardItem $item)
    {
        $this->_dashboardContent[] = $item;

    }//addDashboardContent

     // ------------------------------------

    public function getPermittedModules()
    {
        return $this->_permittedModules;

    }//getPermittedModules

    // ------------------------------------

    protected function addPermittedModule(\User\Elements\Module $module)
    {
        $this->_permittedModules[] = $module;

    }//addPermittedModule


     // ------------------------------------

    public function getMyUsageMenus()
    {
        return $this->_myUsageMenus;

    }//getMyUsageMenus

    // ------------------------------------

    public function addQueryDescripter(\User\Elements\QueryDescripter $query_descripter)
    {
        $query_groupname = $query_descripter->getQueryGroupname();
        $query_realm = $query_descripter->getRealmName();
        $query_group_by_name = $query_descripter->getGroupByName();

        if(!isset($this->_querys[$query_groupname]))
        {
            $this->_querys[$query_groupname] = array();
        }

        if(!isset($this->_querys[$query_groupname][$query_realm]))
        {
            $this->_querys[$query_groupname][$query_realm] = array();
        }

        if(!isset($this->_querys[$query_groupname][$query_realm][$query_group_by_name]))
        {
            $this->_querys[$query_groupname][$query_realm][$query_group_by_name] = array();
        }

        $this->_querys[$query_groupname][$query_realm][$query_group_by_name][($query_descripter->getDefaultStatisticName()==='all'?'all':$query_descripter->getDefaultStatisticName().'-'.$query_descripter->getDefaultQueryType())] = $query_descripter;
    }//addQuery

    public function getQueryDescripters($query_groupname, $realm_name = NULL, $group_by_name = NULL, $statistic_name = NULL, $flatten = false)
    {
        if($query_groupname === 'my_usage') $query_groupname = 'tg_usage';
        //echo "$query_groupname, $realm_name, $group_by_name, $statistic_name";
        if(isset($this->_querys[$query_groupname]))
        {
            if(isset($realm_name))
            {
                if(isset($this->_querys[$query_groupname][$realm_name]))
                {
                    if(isset($group_by_name))
                    {
                        if(isset($this->_querys[$query_groupname][$realm_name][$group_by_name]))
                        {
                            if(isset($statistic_name))
                            {
                                if(isset($this->_querys[$query_groupname][$realm_name][$group_by_name][$statistic_name.'-timeseries']))
                                {
                                    return $this->_querys[$query_groupname][$realm_name][$group_by_name][$statistic_name.'-timeseries'];
                                }
                                else
                                if(isset($this->_querys[$query_groupname][$realm_name][$group_by_name][$statistic_name.'-aggregate']))
                                {
                                    return $this->_querys[$query_groupname][$realm_name][$group_by_name][$statistic_name.'-aggregate'];
                                }
                                else
                                {
                                    $qd = $this->_querys[$query_groupname][$realm_name][$group_by_name]['all'];
                                    $qd->setDefaultStatisticName($statistic_name);
                                    return $qd;
                                }
                            }
                            else
                            {
                                return $this->_querys[$query_groupname][$realm_name][$group_by_name]['all'];
                            }
                        }
                        else
                        {
                            return array();
                        }
                    }
                    else
                    {
                        if($flatten)
                        {
                            $ret = array();
                            foreach($this->_querys[$query_groupname][$realm_name] as $query_descripters_group_realm)
                            {
                                foreach($query_descripters_group_realm as $query_descripter)
                                {
                                    $ret[] = $query_descripter;
                                }
                            }

                            $order_column = array();
                            foreach ($ret as $key => $query_descripter) {
                                $order_column[$key]  = $query_descripter->getOrderId();

                            }
                            array_multisort($order_column, SORT_ASC, $ret);
                            return $ret;
                        }
                        else
                        return $this->_querys[$query_groupname][$realm_name];
                    }

                }else
                {
                    return array();
                }
            }else
            {
                if($flatten)
                {
                    $ret = array();
                    foreach($this->_querys[$query_groupname] as $query_descripters_in_query_group)
                    {
                        foreach($query_descripters_in_query_group as $query_descripters_group_realm)
                        {
                            foreach($query_descripters_group_realm as $query_descripter)
                            {
                                $ret[] = $query_descripter;
                            }
                        }
                    }
                    $order_column = array();
                    foreach ($ret as $key => $query_descripter) {
                        $order_column[$key]  = $query_descripter->getOrderId();

                    }
                    array_multisort($order_column, SORT_ASC, $ret);
                    return $ret;
                }
                else
                return $this->_querys[$query_groupname] ;
            }
        }
        return array();
    }//getQueryDescripters

    public function getAllQueryRealms($query_groupname)
    {
        if (isset($this->_querys[$query_groupname])) {
            return $this->_querys[$query_groupname];
        }
        return array();
    }//getAllQueryRealms

    public function getAllGroupNames()
    {
        return array_keys($this->_querys);
    }

    // ------------------------------------

    public function getFormalName()
    {
        $pdo = DB::factory('database');

        $roleData = $pdo->query("SELECT description FROM Roles WHERE abbrev='{$this->_identifier}'");

        if (count($roleData) == 0) {
            return '';
        }

        return $roleData[0]['description'];

    }//getFormalName

    // ------------------------------------

    // getIdentifier:  Returns the role identifier initially passed into the constructor on behalf of the child classes.
    // If $absolute_identifier is set to true and the role is organization-specific, that organization data will be appended
    // to the identifier. (e.g. 'cd;574' as opposed to simply 'cd') -- this logic is implemented in the role definitions themselves.

    public function getIdentifier($absolute_identifier = false)
    {
        return $this->_identifier;

    }//getIdentifier

    // ------------------------------------

    // The factory method will determine which Role definition to load, based on the value of $role.
    // The role object returned can then take user data into account when determining proper parameters
    // (by means of consulting moddb.UserRoleParameters).

    public static function factory($role)
    {
        if (!(isset($role)))
        {
            throw new \Exception("A role identifier must be specified");
        }

        $role_class = '\\User\\Roles\\'.str_replace(' ', '', $role).'Role';

        $role_definition_file = dirname(__FILE__).'/Roles/'.str_replace(' ', '', $role).'Role'.'.php';

        if (!file_exists($role_definition_file))
        {
            throw new \Exception("Role definition file could not be found for '$role'");
        }

        require_once($role_definition_file);

        // Ensure that the class has been loaded / recognized...

        if (!class_exists($role_class))
        {
            throw new \Exception("Role definition file could not be found for '$role' $role_class");
        }

        // This call will invoke the role's constructor, ultimately assigning $this->_identifier
        return new $role_class();

    }//factory


    public function getRoleCategories($exclude_xsede_category = false)
    {
        if ($exclude_xsede_category == true) {
            unset($this->_roleCategories['tg']);
        }

        return $this->_roleCategories;
    }

    public function getMinMaxDates()
    {
        $pdo = DB::factory('database');
        $min_max_job_date = $pdo->query(
            "select date(min_job_date) as min_job_date, date(max_job_date) as max_job_date from modw.minmaxdate"
        );

        $min_date = $min_max_job_date[0]['min_job_date'];
        $max_date = $min_max_job_date[0]['max_job_date'];
        return json_encode(array('min_date'=>$min_date, 'max_date'=>$max_date));
    }

    public function getDisabledMenus($realms)
    {
        $returnData = array();

        foreach($realms as $realm_name)
        {
            $query_descripter_groups = $this->getQueryDescripters('tg_usage', $realm_name);

            foreach($query_descripter_groups as $query_descripter_group)
            {
                foreach($query_descripter_group as $query_descripter)
                {
                    if($query_descripter->getShowMenu() !== true) continue;

                    if($query_descripter->getDisableMenu() )
                    {
                        $returnData[] = array('id' => 'group_by_'.$realm_name.'_'.$query_descripter->getGroupByName(), 'group_by' => $query_descripter->getGroupByName(), 'realm' => $realm_name);
                    }
                }
            }
        }
        return $returnData;
    }

    public function getSummaryCharts()
    {
        return array_map(
            function ($chart) { return json_encode($chart); },
            self::getConfig($this->_identifier, 'summary_charts')
        );
    }

    protected static function getConfigData()
    {
        if (!isset(self::$_config)) {
            $config = Config::factory();
            self::$_config = $config['roles'];
        }

        return self::$_config;
    }

    protected static function getConfig($identifier, $section = null)
    {
        foreach (self::getConfigData() as $data) {
            if ($data['abbrev'] == $identifier) {
                if ($section === null) {
                    return $data;
                } elseif (array_key_exists($section, $data)) {
                    return $data[$section];
                } else {
                    if ($identifier == 'default') {
                        $msg = "No data found for section '$section'";
                        throw new \Exception($msg);
                    }

                    if (array_key_exists('extends', $data)) {
                        return self::getConfig($data['extends'], $section);
                    }

                    return self::getConfig('default', $section);
                }
            }
        }

        throw new \Exception("Unknown role '$identifier'");
    }

}//aRole

?>
