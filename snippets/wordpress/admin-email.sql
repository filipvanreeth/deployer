UPDATE {{ db_prefix }}options SET option_value = '{{ admin_email:email@domain.com }}' WHERE option_name = 'admin_email';
