<?php
namespace App\Http\Traits;

trait ResultTrait {
    /**
     * success response method.
     *
     * @return \Illuminate\Http\Response
     */
    public function sendResponse($request, $result, $message, $count=null, $perPage=10, $currentPage=1)
    {
        $count= (int)$count ?? count($result);
        $response = [
            'success' => true,
            'pagination'=> [
                'perpage' => (int)$perPage,
                'currentPage' => (int)$currentPage ?? 1,
                'total_pages' => ceil($count/(int)$perPage),
                'count' => $count,
            ],
            'data'    => $result,
            'message' => $message,
        ];

        if (!empty($request->get('page_return'))) {
            return redirect($request->page_return);
        }
        $r=response()->json($response, 200, [], JSON_UNESCAPED_UNICODE);
        //echo $r->content();
        return $r;
    }

    /**
     * return error response.
     *
     * @return \Illuminate\Http\Response
     */
    public function sendError($error, $errorMessages = [], $code = 404)
    {
        $response = [
            'success' => false,
            'message' => $error,
        ];
        if(!empty($errorMessages)){
            $response['data'] = $errorMessages;
        }
        $r=response()->json($response, $code, [], JSON_UNESCAPED_UNICODE);
        return $r;
    }
}
