<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\CategoryBlog;
use App\Models\Blog;

class BlogController extends Controller
{
    public function indexBlog(Request $request)
    {
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
    }

    public function storeBlog(Request $request)
    {
        $request->validate([
            'category_blog_id' => 'required|exists:category_blog,category_blog_id',
            'blog_title'       => 'required|string|max:255',
            'blog_text'        => 'required|string',
            'featured_image'   => 'nullable|image|max:2048',
            'images'           => 'nullable|array',
            'images.*'         => 'image|max:2048',
            'status'           => 'required|in:draft,published,archived',
        ]);

        $blog = Blog::create($request->all());

        return response()->json([
            'status'  => 'success',
            'data'    => $blog,
        ], 201);
    }

    public function showAllBlog($id){
        $id = Blog::find($id);

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
        
    }

    public function blogUpdates(Request $request, $id){
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
    }
}