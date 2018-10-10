<?php

namespace App\Http\Middleware;

use App\Services\RequestService;
use Closure;
use App\Http\Request;

class RouterMiddleware {

    /**
     * @var RequestService
     */
    private $requestService;

    public function __construct(RequestService $requestService) {
        $this->requestService = $requestService;
    }

    /**
     * Handle an incoming request and Validate Authorization
     *
     * @param  \App\Http\Request $request
     * @param  \Closure $next
     * @param  int $id
     * @return mixed
     */
    public function handle(Request $request, Closure $next, int $id) {
        $route = routes($id);

        $request->addRouter($route);

        if(isset($route->permission) && count($route->permission) > 0) {
            $body = [
                "permissions" => $route->permission
            ];
            $headers = $this->requestService->getHeaders($request, null, [
                "content-type" => "text/json"
            ]);

            $response = $this->requestService
                ->request("POST", app("config")["gateway"]["permission_endpoint"], json_encode($body), $headers);
            if(!(is_object($response) && $response->message === "Authorized")){
                return response('Unauthorized.', 401);
            }
        }

        $response = $next($request);

        if(isset($route->responses) && count($route->responses) > 0) {
            foreach($route->responses as $key => $value){
                if($response->getStatusCode() == $key){
                    return response($value, $key);
                }
            }
        }

        return $response;
    }

}
