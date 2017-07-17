<?php

/*check if the flow day match the previous working day*/
?>
<script type="text/javascript">
//<![CDATA[
function getWorkingDay(additionalDays) {
	// Check if parameter 'daysFromToday' is correct type
	if(!isNaN(parseInt(additionalDays))) 
		days = parseInt(additionalDays);
	else days = 0;
	
	// Get today's date and set new date based on parameter value
	var today = new Date();
	today.setDate(today.getDate() + days);

	// Sun, minus two days to get to Friday
    if (today.getDay() == 0) {
		today.setDate(today.getDate() - 2);
    }
	// Sat, minus one day to get to Friday
    else if (today.getDay() == 6) {
		today.setDate(today.getDate() - 1);
	} 
	
	// Return required date string
	return (today.getFullYear() + "-" + (today.getMonth() + 1) + "-" + today.getDate());
}

                          var previous_workingday = getWorkingDay(-1);
                          //compare two day
                          var flowday = new Date(html.date);
                          var lastworkingday = new Date(getWorkingDay(-1));
                          if (flowday != lastworkingday) $("#dailyshop").find('div#flowdate').append("<br />Previous working day(" + previous_workingday + ")");
//]]>

</script>






