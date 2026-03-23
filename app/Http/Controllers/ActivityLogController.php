<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ActivityLog;
use App\Models\Account;
use App\Models\Product;
use Exception;

class ActivityLogController extends Controller
{
    public function logFetch(Request $request){
        try{

        $query =ActivityLog::with('account')
                        ->orderBy('logged_at', 'desc');


        //category tab filter
        $category = $request ->input('category', 'all');
        if ($category && $category !== 'all'){
                $query -> where('category', $category);
        }

        //search
        if($request->filled('search')){
            $query->search($request->search);
        }


        //from date and to date

        if ($request->filled('date_from')){
            $query->whereDate('logged_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')){
            $query->whereDate('logged_at', '<=', $request->date_to);
        }

        $logs = $query->paginate(20);

        $groupedLogs = $logs->getCollection()
                    ->groupBy(fn($log) => $log->logged_at->format('l, F j, Y'));

        return response()->json([
            'status' => 'success',
            'data' => [
                'grouped' => $groupedLogs,
                'categories' => $this->getCategories(),
                'pagination'=>[
                    'current_page' => $logs->currentPage(),
                    'last_page' => $logs->lastPage(),
                    'per_page' => $logs->perPage(),
                    'total' => $logs->total(),
                ],
            ],
        ]); 

        }catch(Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function showLogs(Request $request, ActivityLog $activityLog){
        try{

                $activityLog->load('account', 'product');

                if($request->expectsJson()){
                    return response()->json(['data' => $activityLog]);
                }

                return response()->json([
                    'status' => 'success',
                    'data' => $activityLog,
                ]);

            }catch(Exception $e){
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ], 500);
            }
    }

    public function storeLogs(Request $request){
        try{

            $validatedData = $request->validate([
                'user_id' => 'required|exists:users,id',
                'action' => 'required|string|max:255',
                'category' => 'required|in:orders,stock,account,blogs,payments,backups,other',
                'product_name' => 'nullable|string|max:255',
                'product_unique_code' => 'nullable|string|max:100',
                'mode_of_payment' => 'nullable|string|max:100',
                'amount' => 'nullable|numeric|min:0',
                'description' => 'nullable|string',
            ]);
            
            $user = \App\Models\User::find($validatedData['user_id']);

            $log = ActivityLog::log(
                    $user,
                    $validatedData['action'],
                    $validatedData['category'],
                    $validatedData
            );
            
            if($request ->expectsJson()){
                return response()->json([
                    'status' => 'error',
                    'data' => $log, 
                    'message' => 'Activity Logged.' 
                ], 201);
            }


            

        }catch(\Illuminate\Validation\ValidationException $e){
            return response()->json([
                'status' => 'error',
                'type' => 'validation',
                'message' => $e->errors(),
            ], 422);

        }catch(Exception $e){
            return response()->json([
                'status' => 'error',
                'type' => 'server',
                'message' => $e->getMessage(),
            ], 500);
        }
    }


            //udpate for logs
    public function updateLogs(Request $request, ActivityLog $activityLog){
        try{

            $validateDate = $request->validate([
                'action'              => 'sometimes|string|max:255',
                'category'            => 'sometimes|in:orders,stock,account,blogs,payments,backups,other',
                'product_name'        => 'nullable|string|max:255',
                'product_unique_code' => 'nullable|string|max:100',
                'mode_of_payment'     => 'nullable|string|max:100',
                'amount'              => 'nullable|numeric|min:0',
                'reference_table'     => 'nullable|string|max:100',
                'reference_id'        => 'nullable|integer',
                'description'         => 'nullable|string',
            ]);

            $activityLog ->update($validateDate);

            return response()->json([
                'status' => 'success',
                'message' => 'Activity log update successfully',
                'data' => $activityLog->fresh()->load('account'),
            ]);

        }catch(\Illuminate\Validation\ValidationException $e){
            return response()->json([
                'status' => 'error',
                'type' => 'validation',
                'message' => $e->errors(),
            ], 422);

        }catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'type' => 'server',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroyLogs(ActivityLog $activityLog){
        try{

        $activityLog -> delete();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Activity log deleted successfully.',
            ]);

        } catch (Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }   

    public function delallLogs(Request $request){
        try{
            $category = $request->input('category', 'all');
            if($category === 'all' ){
                ActivityLog::truncate();
                $message = 'All activity logs are cleared';
            } else {
                ActivityLog::where ('category', $category)->delete();
                $message = ucfirst($category) . 'activity logs cleared successfully';
            }

            return response()->json([
                'status' => 'success',
                'message' => $message,
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    private function getCategories(): array{
        return[
            ['key' => 'all' ,   'label' =>'ALL'],
            ['key' => 'orders', 'label' => 'Orders'],
            ['key' => 'stock' , 'label' => 'Stock'],
            ['key' => 'account',  'label' => 'Account'],
            ['key' => 'blogs',    'label' => 'Blogs'],
            ['key' => 'payments', 'label' => 'Payments'],
            ['key' => 'backups',  'label' => 'Backups'],
        ];
    }

}



