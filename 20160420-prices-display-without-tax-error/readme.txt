it's reported that the price(s) are display without tax prior to checkout

this is because: after the function "20160401-createaccount_without_address" was added the following function was not updated accordingly. example: when the new user register but not yet update the address the default one would not have a country id or zone id. which is need by tep_get_tax_rate(), tep_get_tax_description()

the solution is to add logic to those functions

file changes:

/includes/functions/general.php

