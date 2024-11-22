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

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $userValidate = Validator::make(
            $request->all(),
            [
                'title' => 'required',
                'description' => 'required',
                'image' => 'required|mimes:png,jpg,jpeg,webp,gif'
            ]

        );

        if ($userValidate->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $userValidate->errors()->all()
            ], 401);
        }

        $img = $request->image;
        // $ext = $img->getClientOriginalExtension();
        $ext = $img->extension();
        $imageName = time() . '.' . $ext;
        $img->move(public_path() . '/uploads/posts', $imageName);

        $post = Post::create([
            'title' => $request->title,
            'description' => $request->description,
            'image' => $request->$imageName
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
        $userValidate = Validator::make(
            $request->all(),
            [
                'title' => 'required',
                'description' => 'required',
                'image' => 'required|mimes:png,jpg,jpeg,webp,gif'
            ]

        );

        if ($userValidate->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $userValidate->errors()->all()
            ], 401);
        }

        $post = Post::select('id', 'image')->get();
        if ($request->image != '') {
            $path = public_path() . '/uploads/posts';
            if ($post->image != '' && $post->image != null) {
                $oldFile = $path . $post->image;
                if (fileExists($oldFile)) {
                    unlink($oldFile);
                }
            }

            $img = $request->image;
            // $ext = $img->getClientOriginalExtension();
            $ext = $img->extension();
            $imageName = time() . '.' . $ext;
            $img->move(public_path() . '/uploads/posts', $imageName);
        } else {
            $imageName = $post->image;
        }


        $post = Post::where(['id' => $id])->update([
            'title' => $request->title,
            'description' => $request->description,
            'image' => $request->$imageName
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
    public function destroy(string $id)
    {
        $imagePath = Post::select('image')->where('id', $id)->get();
        $filePath = public_path() . '/uploads/posts' . $imagePath[0]['image'];
        $post = Post::where('id', $id)->delete();

        unlink($filePath);

        return response()->json([
            'status' => true,
            'message' => 'Post Deleted Successfully',
            'post' => $post,
        ], 200);
    }
}
