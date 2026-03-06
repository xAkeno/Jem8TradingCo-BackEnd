<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CategoryBlog;
use App\Models\Blog;



class BlogController extends Controller
{
    //Get all blogs
    public function indexBlog(Request $request)
    {
        try{
        
        $query = Blog::with('category');
        
        if ($request->has('category')){
            $categoryName = $request->input('category');
            $query->whereHas('category', function($q) use ($categoryName) {
                $q->where('category_name', $categoryName);
            });
        }
        
            $blogs = $query->get();

        return response()->json([
            'status'  => 'success',
            'data'    => $blogs,
        ], 200);
        
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'type' => 'server',
                'message' => $e->getMessage()
            ], 500);
        }
        
    }

    // CREATE BLOG
    public function storeBlog(Request $request)
    {
        try{
                $request->validate([
                'category_name'    => 'required|string|max:255',
                'blog_title'       => 'required|string|max:255',
                'blog_text'        => 'required|string',
                'featured_image'   => 'nullable|image|max:5120',
                'images'           => 'nullable|array',
                'images.*'         => 'image|max:5120',
                'status'           => 'required|in:draft,published,archived',
            ]);

            $category =CategoryBlog::firstOrCreate([
                'category_name' => $request->input('category_name')
            ]);
            
            
            $data =[
                'category_blog_id' => $category->category_blog_id,
                'blog_title'       => $request->blog_title,
                'blog_text'        => $request->blog_text,
                'status'           => $request->status,
            ];

            if ($request->hasFile('featured_image')) {
                $data['featured_image'] = $request->file('featured_image')->store('featured_images', 'public');
            }

            if ($request->hasFile('images')) {
                $data['images'] = [];
                foreach ($request->file('images') as $image) {
                    $data['images'][] = $image->store('blog_images', 'public');
                }
            }

            $blog = Blog::create($data);

            return response()->json([
                'status'  => 'success',
                'data'    => $blog,
            ], 201);

        } catch(\Illuminate\Validation\ValidationException $e){
            return response()->json([
                'status' => 'error',
                'type' => 'validation',
                'message' => $e->errors()
            ], 422);

        } catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'type' => 'server',
                'message' => $e->getMessage()
            ], 500);
        }

    }

    // GET BLOG BY ID
    public function showAllBlog($id){
        try{

            $blog = Blog::with('category')->find($id);
        
        
        if(!$id){
            return(response()->json([
                'status'=>'error',
                'message'=>'Blog not found'
            ],404));
        }else{
            return(response()->json([
                'status'=>'success',
                'data'=>$id
            ],200));
        }

        }catch(\Illuminate\Database\Eloquent\ModelNotFoundException $e){
            return response()->json([
                'status' => 'error',
                'type' => 'not_found',
                'message' => 'Blog not found'
            ], 404);

        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'type' => 'server',
                'message' => $e->getMessage()
            ], 500);
        }
    }

        // UPDATE BLOG
    public function blogUpdates(Request $request, $id){
        try{
        
        $blog = Blog::find($id);
        if(!$blog){
            return response()->json([
                'status' => 'error',
                'message' => 'Blog not found'
            ], 404);
        }else{
            $request->validate([
                'category_blog_id' => 'sometimes|exists:category_blog,category_blog_id',
                'blog_title'       => 'sometimes|string|max:255',
                'blog_text'        => 'sometimes|string',
                'featured_image'   => 'sometimes|image|max:2048',
                'images'           => 'sometimes|array',
                'images.*'         => 'image|max:2048',
                'status'           => 'sometimes|in:draft,published,archived',
            ]);

            $blog->update($request->all());

            return response()->json([
                'status'  => 'success',
                'data'    => $blog,
            ], 200);
        }

        }catch(\Illuminate\Database\Eloquent\ModelNotFoundException $e){
            return response()->json([
                'status' => 'error',
                'type' => 'not_found',
                'message' => 'Blog not found'
            ], 404);

        }catch(\Illuminate\Validation\ValidationException $e){
            return response()->json([
                'status' => 'error',
                'type' => 'validation',
                'message' => $e->errors()
            ], 422);

        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'type' => 'server',
                'message' => $e->getMessage()
            ], 500);
        }
    }

        // DELETE BLOG
    public function deleteBlog($id){

        try{

        $blog = Blog::find($id);
        
        if(!$blog){
            return response()->json([
                'status' => 'error',
                'message' => 'Blog not found'
            ], 404);
        }
        $blog->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Blog deleted successfully'
        ], 200);

        }catch(\Illuminate\Database\Eloquent\ModelNotFoundException $e){
            return response()->json([
                'status' => 'error',
                'type' => 'not_found',
                'message' => 'Blog not found'
            ], 404);
        
            }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'type' => 'server',
                'message' => $e->getMessage()
            ], 500);
        }
        
    }
}