<?php
/*
 * @author: Amin Ghadersohi 7/1/2010
 *
 */
class TimeseriesGenerator
{
    private $_time_period;
    function __construct($time_period)
    {
        $this->_time_period = $time_period;
        if ($time_period != 'day' && $time_period != 'week' && $time_period != 'month' && $time_period != 'quarter' && $time_period != 'year') {
            throw new Exception("Time period {$this->_time_period} is invalid.");
        }
    }
    
    
    function execute($modwdb, $dest_schema)
    {
        $min_max_job_date = $modwdb->query("select 
				date_sub(date(min(min_job_date)), interval 1 day) as min_date,
				date_add(date(now()), interval 1 day) as max_date,
				greatest(max_job_date, now()) as max_job_date
				from modw.minmaxdate");
        
        $min_date     = $min_max_job_date[0]['min_date'];
        $max_date     = $min_max_job_date[0]['max_date'];
        $max_job_date = $min_max_job_date[0]['max_job_date'];

        if ($min_date == '') {
            throw new Exception('No min date found in minmaxdate');
        }
        
        $modwdb->handle()->prepare("truncate table {$this->_time_period}s")->execute();
        $date = $min_date;
        
        while ($date < $max_date) {
            $period_formula       = "";
            $period_start_formula = "";
            $period_end_formula   = "";
            $period_interval      = "";
            if ($this->_time_period == 'day') {
                $period_formula       = "dayofyear('$date')";
                $period_start_formula = " timestamp(date('$date'))";
                $period_end_formula   = " timestamp(date('$date') , '23:59:59')";
                $period_interval      = "1 day";
            } else if ($this->_time_period == "week") {
                $period_formula       = "case when (floor((dayofyear( '$date')-1)/7.0 ) = 52) then 51 else floor((dayofyear( '$date')-1)/7.0 ) end";
                $period_start_formula = "timestamp(makedate (year('$date'), case when (floor((dayofyear( '$date')-1)/7.0 ) = 52) then 51 
																									   else floor((dayofyear( '$date')-1)/7.0 ) 
																								  end * 7 + 1), '00:00:00')";
                $period_end_formula   = " timestamp(case when ( $period_formula = 51) then date(concat( year('$date'), '-12-31')) 
													       else makedate( year('$date'),  ( $period_formula+1) * 7)
																			   end , '23:59:59')";
                $period_interval      = "7 day";
            } else if ($this->_time_period == "month") {
                $period_formula = "month('$date')";
                
                $period_start_formula = "timestamp(makedate (year('$date'), dayofyear( '$date')-dayofmonth('$date')+1 ) , '00:00:00')";
                $period_end_formula   = " timestamp(last_day('$date') , '23:59:59')";
                $period_interval      = "7 day";
            } else if ($this->_time_period == 'quarter') {
                $period_formula = "quarter('$date')";
                
                $period_start_formula = "timestamp(TIMESTAMPADD(QUARTER,quarter('$date')-1,makedate(year('$date'),1)) , '00:00:00')";
                $period_end_formula   = " timestamp(TIMESTAMPADD(MONTH,quarter('$date')*3,makedate(year('$date'),1)) - interval 1 second , '00:00:00')";
                $period_interval      = "7 day";
            } else if ($this->_time_period == 'year') {
                $period_formula = "year('$date')";
                
                $period_start_formula = "timestamp(makedate (year('$date'), 1 ) , '00:00:00')";
                $period_end_formula   = "timestamp(makedate (year('$date')+1, 1 )- interval 1 second , '00:00:00')";
                $period_interval      = "365 day";
            }
            $seconds_forumla    = "((least(unix_timestamp($period_end_formula), unix_timestamp('$max_job_date')) - unix_timestamp($period_start_formula)) + 1 )";
            $period_end_formula = "least($period_end_formula, '$max_job_date')";
            if ($this->_time_period == 'year') {
				
			
                $insert_statement = "insert ignore into {$this->_time_period}s (id, `year`, 
										{$this->_time_period}_start, 
										{$this->_time_period}_end,
										hours,
										seconds,
										{$this->_time_period}_start_ts, 
										{$this->_time_period}_end_ts, 
										{$this->_time_period}_middle_ts) 
									values 
										(
										 (year('$date')*100000),
										 year('$date'),
										 $period_start_formula,					  
										 $period_end_formula,
										 $seconds_forumla/3600.00,
										 $seconds_forumla,
										 unix_timestamp($period_start_formula),					  
										 unix_timestamp($period_end_formula),
										 unix_timestamp($period_start_formula) + (unix_timestamp($period_end_formula) - unix_timestamp($period_start_formula))/2
										 )";
            } else {
                $insert_statement = "insert ignore into {$this->_time_period}s (id, `year`, 
									   `{$this->_time_period}`,
										{$this->_time_period}_start, 
										{$this->_time_period}_end,
										hours,
										seconds,
										{$this->_time_period}_start_ts, 
										{$this->_time_period}_end_ts, 
										{$this->_time_period}_middle_ts) 
									values 
										(
										 (year('$date')*100000)+$period_formula,
										 year('$date'),
										 $period_formula,
										 $period_start_formula,					  
										 $period_end_formula,
										 $seconds_forumla/3600.00,
										 $seconds_forumla,
										 unix_timestamp($period_start_formula),					  
										 unix_timestamp($period_end_formula),
										 unix_timestamp($period_start_formula) +  (unix_timestamp($period_end_formula) - unix_timestamp($period_start_formula))/2
										 )";
            }
            //	echo $insert_statement;
            $modwdb->handle()->prepare($insert_statement)->execute();
            
            $date_query = $modwdb->query("select date_add('$date', interval $period_interval) as d");
            $date       = $date_query[0]['d'];
            
        }
    }
}

?>


