<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\admin_leadership;


class AdminLeadershipController extends Controller
{
    public function index(Request $request){
        try{

        $query = admin_leadership::with('leader');

        if ($request -> has('leader')){
            $leaders = $request->input('leader');

            $query->whereHas('leader', function($q) use ($leaders){
                $q->where('id', $leaders);
            });
        }

        $leaderships = $query->get();

        return response()->json([
            'status' => 'success',
            'data' => $leaderships
        ], 200);

        }catch(\Exception $e){
            return response()->json([
                    'status' => 'error',
                    'type' => 'server',
                    'message' => $e->getMessage()
                ], 500);
        }

        
    }

    public function adminImgStore(Request $request){
        try {
            $request->validate([
                'user_id'       => 'required|exists:users,id',
                'position'      => 'required|string|max:255',
                'status'        => 'required|bolean',
                'leadership_img'=> 'required|image|mimes:jpeg,png,jpg|max:2048',
            ]);

            // Handle image upload
            $imagePath = null;
            if ($request->hasFile('leadership_img')) {
                $imagePath = $request->file('leadership_img')->store('leadership_imgs', 'public');
            }

            $leadership = admin_leadership::create([
                'user_id'        => $request->user_id,
                'position'       => $request->position,
                'status'         => $request->status,
                'leadership_img' => $imagePath,
            ]);

            return response()->json([
                'status' => 'success',
                'data'   => $leadership,
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status'  => 'error',
                'type'    => 'validation',
                'message' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'type'    => 'server',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
