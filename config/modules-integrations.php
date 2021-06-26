<?php

return [
    'title' => 'Integrations',
    'config' => [
    	'hubspot' => [
            'display_name' => 'Hubspot',
            'description' => 'Integrate Hubspot to supercharge your Customer Operations',
    		"oauth_url" => "https://app.hubspot.com/oauth/authorize?scope=contacts%20social&redirect_uri=".env('APP_URL', 'https://hub.dorcas.io')."/mit/integrations-oauth-callback/dorcas-hubspot&client_id=dfbc9611-be13-4dfb-8ec6-f981c4cf5710",
    		"oauth_token_url" => "https://api.hubapi.com/oauth/v1/token",
    		"oauth_token_method" => "POST",
    		"oauth_token_params" => [
    			"grant_type" => "authorization_code",
    			"client_id" => "dfbc9611-be13-4dfb-8ec6-f981c4cf5710",
    			"client_secret" => "dd5ed80c-d4a3-444e-a700-fa730ab81d57",
    			"redirect_uri" => env('APP_URL', 'https://hub.dorcas.io')."/mit/integrations-oauth-callback/dorcas-hubspot",
    			"code" => ""
    		],
    		"oauth_token_headers" => [
    			"Content-Type: application/x-www-form-urlencoded"
    		],
    		"oauth_callback_key" => "dorcas-hubspot",
    		"oauth_callback_param" => "code",
    		"portal_url" => "https://api.hubapi.com/integrations/v1/me",
    		"portal_method" => "GET",
    		"portal_response_param" => "portalId"
    	]
    ]
];