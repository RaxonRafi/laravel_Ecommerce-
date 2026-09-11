<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Subcategory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('checkrole');
    }

    public function index()
    {
        return view('category.index', [
            'categories' => Category::all(),
            'deleted_categories' => Category::onlyTrashed()->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        return view('category.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'category_name' => 'required|unique:categories',
            'category_photo' => 'required|image',

        ]);
        // photo upload code start
        // step:1-Profile photo name create Ex-(1.jpg)
        $new_name = auth()->id().'-'.Str::random(5).'.'.$request->file('category_photo')->getClientOriginalExtension();
        // step:2-Profile photo upload
        $save_link = base_path('public/uploads/category_photo/').$new_name;
        Image::read($request->file('category_photo'))->resize(600, 328)->save($save_link);
        // photo upload code end
        Category::insert([
            'category_name' => $request->category_name,
            'created_by' => auth()->id(),
            'created_at' => Carbon::now(),
            'category_photo' => $new_name,
        ]);

        return back()->with('success_message', 'Category Created Successfully!');
    }

    /**
     * Display the specified resource.
     *
     * @return Response
     */
    public function show(Category $category)
    {
        return view('category.show', compact('category'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(Category $category)
    {
        return view('category.edit', compact('category'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(Request $request, Category $category)
    {
        $request->validate([
            'category_name' => 'required|unique:categories',

        ]);
        $category->category_name = $request->category_name;
        $category->updated_by = auth()->id();
        $category->save();

        return back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy(Category $category)
    {
        // $category->delete();
        $category->delete();

        return back();
    }

    public function restore($id)
    {
        Category::onlyTrashed()->where('id', $id)->restore();

        return back();
    }

    public function forcedelete($id)
    {
        Category::onlyTrashed()->where('id', $id)->forceDelete();
        Subcategory::where('category_id', $id)->forceDelete();

        return back();
    }
}
