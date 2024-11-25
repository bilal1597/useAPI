<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use function PHPUnit\Framework\fileExists;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data['posts'] = Post::all();
        return response()->json([
            'status' => true,
            'message' => 'All Posts',
            'data' => $data
        ], 200);
    }

    // public function store(Request $request)
    // {
    //     $userValidate = Validator::make(
    //         $request->all(),
    //         [
    //             'title' => 'required',
    //             'description' => 'required',
    //             'image' => 'nullable|mimes:png,jpg,jpeg,webp,gif'
    //         ]

    //     );

    //     if ($userValidate->fails()) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Validation Error',
    //             'errors' => $userValidate->errors()->all()
    //         ], 401);
    //     }

    //     $img = $request->image;
    //     $ext = $img->getClientOriginalExtension();
    //     $imageName = time() . '.' . $ext;
    //     $img->move(public_path() . '/uploads/posts/', $imageName);

    //     $post = Post::create([
    //         'title' => $request->title,
    //         'description' => $request->description,
    //         'image' => $request->$imageName
    //     ]);

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Post Created Successfully',
    //         'post' => $post,
    //     ], 200);
    // }

    public function store(Request $request)
    {
        // request ki validation k lye
        $userValidate = Validator::make(
            $request->all(),
            [
                'title' => 'required',
                'description' => 'required',
                'image' => 'nullable|mimes:png,jpg,jpeg,webp,gif'
            ]
        );

        if ($userValidate->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $userValidate->errors()->all()
            ], 401);
        }

        // Handle image upload if  provided
        if ($request->hasFile('image')) {
            $img = $request->file('image');
            $ext = $img->getClientOriginalExtension();
            $imageName = time() . '.' . $ext;

            // Move the image to the 'uploads/posts' folder
            $img->move(public_path('uploads/posts'), $imageName);

            // Save image path to Db
            $imagePath = 'uploads/posts/' . $imageName;
        } else {
            $imagePath = null; // Agr image image upload na ho
        }

        // Create the post record
        $post = Post::create([
            'title' => $request->title,
            'description' => $request->description,
            'image' => $imagePath // Save image path to database
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Post Created Successfully',
            'post' => $post,
        ], 200);
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $data['post'] = Post::select(
            'id',
            'title',
            'description',
            'image',
        )->where(['id' => $id])->get();
        return response()->json([
            'status' => true,
            'message' => 'Single Post',
            'data' => $data,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Validate the request
        $userValidate = Validator::make(
            $request->all(),
            [
                'title' => 'required',
                'description' => 'required',
                'image' => 'nullable|mimes:png,jpg,jpeg,webp,gif'
            ]
        );

        if ($userValidate->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $userValidate->errors()->all()
            ], 401);
        }

        // Retrieve the existing post image
        $postImage = Post::select('id', 'image')
            ->where('id', $id)
            ->first(); // Using first() instead of get()

        // If a new image is uploaded
        if ($request->hasFile('image')) {
            $path = public_path() . '/uploads/posts/';

            // If there is an old image, delete it
            if ($postImage && $postImage->image) {
                $oldFile = $path . $postImage->image;
                if (file_exists($oldFile)) { // Fix the file_exists function
                    unlink($oldFile);
                }
            }

            // Get the new image's extension and save it
            $img = $request->file('image');
            $ext = $img->extension(); // Can also use getClientOriginalExtension()
            $imageName = time() . '.' . $ext;
            $img->move($path, $imageName);

            // Store the relative image path
            $imagePath = 'uploads/posts/' . $imageName;
        } else {
            // If no new image is uploaded, keep the old one
            $imagePath = $postImage->image;
        }

        // Update the post with new data
        $post = Post::where('id', $id)->update([
            'title' => $request->title,
            'description' => $request->description,
            'image' => $imagePath // Store the image path
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Post Updated Successfully',
            'post' => $post,
        ], 200);
    }


    /**
     * Remove the specified resource from storage.
     */
    // public function destroy(string $id)
    // {
    //     $imagePath = Post::select('image')->where('id', $id)->get();
    //     $filePath = public_path() . '/uploads/posts/' . $imagePath[0]['image'];
    //     unlink($filePath);

    //     $post = Post::where('id', $id)->delete();



    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Post Deleted Successfully',
    //         'post' => $post,
    //     ], 200);
    // }

    public function destroy(string $id)
    {
        // Retrieve the post by id
        $post = Post::select('image')
            ->where('id', $id)->first(); // Use first() instead of get()

        // Check if the post exists
        if (!$post) {
            return response()->json([
                'status' => false,
                'message' => 'Post not found',
            ], 404);
        }

        // If the post has an image, delete the image file
        if ($post->image) {
            $filePath = public_path('uploads/posts/' . $post->image);

            // Check if the file exists before trying to delete it
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        // Delete the post record from the database
        $postDeleted = Post::where('id', $id)->delete();

        if ($postDeleted) {
            return response()->json([
                'status' => true,
                'message' => 'Post Deleted Successfully',
            ], 200);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete post',
            ], 500);
        }
    }
}
