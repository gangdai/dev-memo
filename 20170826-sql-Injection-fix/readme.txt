Sql Injection issue fixed in new classes

htop shows unusual high cpu usage and mysql consume a very high memory usage also causing network connection problem (sagepay). Examine the mysql slow query log the following queries (examples) need to be addressed:

[code lang='css']
...
pd.language_id = '1' and m.manufacturers_id in (if(now()=sysdate(),sleep(12.327),0)/*\'XOR(if(now()=sysdate(),sleep(12.327),0))OR\'\"XOR(if(now()=sysdate(),sleep(12.327),0))OR\"*/)
...
[/code]

[code lang='css']
...
opv.products_variants_values_id in (if(now()=sysdate(),sleep(4),0)/*\'XOR(if(now()=sysdate(),sleep(4),0))OR\'\"XOR(if(now()=sysdate(),sleep(4),0))OR\"*/)
...
[/code]

The above are the actual sql run and it cause lots of memory/cpu and causeing mysql service busy and the netowork connection issue mentioned. The above related queries constructor are located in the recent added classed:

pageinfo.php
product_listing.php

A temporary fix to the accoring sql condition functions found in the above class (example):

Change from:
[code lang='css']
    function getCondOpt($input) {
        $cond_input_a = explode("|", $input);
        $cond = "";
        foreach ( $cond_input_a as $k => $v ) {
            $cond .= $v . ",";
        }

        return substr($cond, 0,-1);

    }
[/code]

To:
[code lang='css']
    function getCondOpt($input) {
        $cond_input_a = explode("|", $input);
        $cond = "";
        foreach ( $cond_input_a as $k => $v ) {
            if (tep_not_null($v) && is_numeric($v)) {
                $cond .= $v . ",";
            }
        }

        if (tep_not_null($cond)) {
            return substr($cond, 0,-1);
        }
        else {
            return "0";
        }
    }
[/code]

After the above change it's back to normal performance.



Sql thrash issue from Redux Framework

htop shows unusual high cpu usage and mysql consume a very high memory usage also causing network connection problem. Examine the mysql slow query log the following queries (examples) need to be addressed, the following query take a 1-2 second to run:

[code lang='css']
...
UPDATE `wp_options` SET `option_value` = '1' WHERE `option_name` = '_transient__redux_activation_redirect';
...
[/code]

The above '_transient__redux_activation_redirect' is found in wordpress dir 'framework/ReduxCore/inc/welcome/welcome.php'

comment the following:

[code lang='css']
update_option( 'redux_version_upgraded_from', ReduxFramework::$_version );
set_transient( '_redux_activation_redirect', true, 30 );
[/code]


