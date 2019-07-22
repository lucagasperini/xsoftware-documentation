<?php
        function instagram_call()
        {
                if (!isset($_GET['code']) || empty($_GET['code']))
                        return;

                $pars = [
                        'client_id' => $this->options['ig']['appid'],
                        'client_secret' => $this->options['ig']['secret'],
                        'grant_type' => 'authorization_code',
                        'redirect_uri' => $this->options['ig']['call'],
                        'code' => $_GET['code']
                ];


                $curlSES=curl_init();

                curl_setopt($curlSES,CURLOPT_URL,'https://api.instagram.com/oauth/access_token');
                curl_setopt($curlSES,CURLOPT_RETURNTRANSFER,true);
                curl_setopt($curlSES,CURLOPT_HEADER, false);
                curl_setopt($curlSES, CURLOPT_POST, true);
                curl_setopt($curlSES, CURLOPT_POSTFIELDS,$pars);
                curl_setopt($curlSES, CURLOPT_CONNECTTIMEOUT,10);
                curl_setopt($curlSES, CURLOPT_TIMEOUT,30);

                $result = json_decode(curl_exec($curlSES));

                curl_close($curlSES);

                /* Get the option using wordpress API */
                $options = get_option('xs_options_socials', array());

                /* Replace the value with access token */
                $options['ig']['token'] = $result->access_token;
                /* Refresh the option deleting the cache */
                wp_cache_delete ( 'alloptions', 'options' );
                /* Update the option on framework and return the value */
                $result = update_option('xs_options_socials', $options);
                var_dump($result, $options['ig']);
        }

                function show_instagram()
        {
                $options = [
                        'name' => 'xs_options_socials[ig][call]',
                        'selected' => $this->options['ig']['call'],
                        'data' => xs_framework::get_wp_pages_link(),
                        'default' => 'Select a instagram page',
                        'echo' => TRUE
                ];

                add_settings_field(
                        $options['name'],
                        'Set instagram page',
                        'xs_framework::create_select',
                        'xs_socials',
                        'xs_socials_section',
                        $options
                );

                $settings_field = [
                        'value' => $this->options['ig']['appid'],
                        'name' => 'xs_options_socials[ig][appid]',
                        'echo' => TRUE
                ];

                add_settings_field(
                        $settings_field['name'],
                        'App ID:',
                        'xs_framework::create_input',
                        'xs_socials',
                        'xs_socials_section',
                        $settings_field
                );

                $settings_field = [
                        'value' => $this->options['ig']['secret'],
                        'name' => 'xs_options_socials[ig][secret]',
                        'echo' => TRUE
                ];

                add_settings_field(
                        $settings_field['name'],
                        'App Secret:',
                        'xs_framework::create_input',
                        'xs_socials',
                        'xs_socials_section',
                        $settings_field
                );

                if(
                        empty($this->options['ig']['secret']) ||
                        empty($this->options['ig']['appid']) ||
                        empty($this->options['ig']['call'])
                )
                        return;

                $url = 'https://api.instagram.com/oauth/authorize/?client_id='.$this->options['ig']['appid'].
                '&redirect_uri='.$this->options['ig']['call'].'&response_type=code';

                $settings_field = [
                        'name' => 'link_instagram',
                        'href' => htmlspecialchars($url),
                        'text' => 'Log in with Instagram!',
                        'echo' => TRUE
                ];
                add_settings_field(
                        $settings_field['name'],
                        'Login instagram:',
                        'xs_framework::create_link',
                        'xs_socials',
                        'xs_socials_section',
                        $settings_field
                );

        }
?>
