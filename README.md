# Skyline API
The Skyline API package offers you an action controllers to manage api requests.  
Its in addition to the Skyline API Component.

### Insallation
```bin
$ composer require skyline/api
```

You may also be interested on ```skyline/component-api``` which defines several javascript routines to access the api.

### Usage
````php
use Skyline\API\Controller\AbstractAPIActionController;
use Symfony\Component\HttpFoundation\Request;
use Skyline\Kernel\Service\CORSService;

class MyAPIActionController extends AbstractAPIActionController {
    // Routing to the action is done by default in the
    // routing configuration or annotation compiler
    public function myAction() {
        // ...
    }
    
    // But this action gets only performed if ...
    public function acceptsAnonymousRequest(Request $request): bool
    {
        // ... the request has an origin header field or
        return SkyGetRunModes() > SKY_RUNMODE_PRODUCTION;
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

    // By version 0.8.5 this method must decide if the request must be verified.
    // This method gets called right before the main action is handled.
    protected function enableCsrfCheck(Request $request): bool {
    	if(strcasecmp($request->getMethod(), 'GET') === false)
    		return true;
        return false;
    }
}
````