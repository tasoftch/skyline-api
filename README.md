# Skyline API
The Skyline API package offers you an action controllers to manage api requests.

Install it using composer:
```bin
$ composer require skyline/api
```

You may also be interested on skyline/component-api which defines several javascript routines to access the api.

##Usage
````php
class MyAPIActionController extends AbstractAPIActionController {
    // Routing to the action is done by default in the routing configuration
    // 
    public function myAction() {
        ...
    }
    
    // But this action gets only performed if ...
    public function acceptsAnonymousRequest(Request $request): bool
    {
        // ... the request has an origin header field or
        return SkyGetRunModes() > SKY_RUNMODE_PRODUCTION ? true : false;
    }
    
    public function acceptsCrossOriginRequest(Request $request): bool
    {
        // ... the request came from the same origin or
        return CORSService::isRegistered( $request->getHost() );
    }
    
    public function acceptOrigin(Request $request, bool &$requireCredentials = false): bool
    {
        // ... the request is cross origin, decide to accept it generally or specified.
        // Also declare, if the request must identify itself.
        return CORSService::getAllowedOriginOf($request, $requireCredentials) ? true : false;
    }
}
````