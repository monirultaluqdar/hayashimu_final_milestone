<?php

namespace App\Http\Controllers;

use App\Contracts\CategoryContract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CategoryController extends BaseController
{
    protected $categoryRepository;

    /**
     * CategoryController constructor.
     * @param CategoryContract $categoryRepository
     */
    public function __construct(CategoryContract $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
   public function index(Request $request)
    {
        $old_data = $request->all();
        $categories = $this->categoryRepository->filterCategories($request);
        $parents = $this->categoryRepository->parentCategories();
        $this->setPageTitle('Category','List of all categories');
        return view('admin.categories.index',compact('categories','parents','old_data'));
    }

    public function create(){
        $parents = $this->categoryRepository->findCategoryByParentId(1);
        $this->setPageTitle('Category','Create New Category');
        return view('admin.categories.create',compact('parents'));
    }

    public function store(Request $request){
        $this->validate($request,[
            'name' => 'required|max:191|unique:categories',
            'sorting' => 'required|numeric',
            'image'     => 'mimes:jpg,jpeg,png,webp|max:5000',
            'hover_image'     => 'mimes:jpg,jpeg,png,webp|max:5000',
            'meta_title' => 'nullable|max:191',
            'meta_description' => 'nullable'
        ]);

        // Debug: Check specifically for textarea fields
    //    dd($request->all());
        
        $params = $request->except('_token');

        $category = $this->categoryRepository->createCategory($params);

        if(!$category){
            return $this->responseRedirectBack('Error occurred while creating category','error',true,true);
        }
        $this->removeCache();
        return $this->responseRedirect('admin.categories.index','Category Added Successfully','success',false,false);

    }

    public function edit($id)
    {
        $category = $this->categoryRepository->findCategoryById($id);
        $parents  = $this->categoryRepository->findCategoryByParentId(1);;
        $this->setPageTitle('Category','Edit Category : '.$category->name);
        return view('admin.categories.edit',compact('category','parents'));
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update(Request $request){

        $this->validate($request,[
            'name' => 'required|max:191',
            'sorting' => 'required|numeric',
            'image'     => 'mimes:jpg,jpeg,png,webp|max:5000',
            'hover_image'     => 'mimes:jpg,jpeg,png,webp|max:5000',
            'meta_title' => 'nullable|max:191',
            'meta_description' => 'nullable|max:500'
        ]);
        $params = $request->except('_token');
        $category = $this->categoryRepository->updateCategory($params);

        if(!$category){
            return $this->responseRedirectBack('Error occurred while updating category','error',true,true);
        }
        $this->removeCache($category->slug);
        return $this->responseRedirectBack('Category updated successfully','success',false,false);
    }

    public function delete($id){
        if($id == 1) {
            return $this->responseRedirectBack('Cannot delete root category (ID: 1)','error',true,true);
        }
        $category = $this->categoryRepository->deleteCategory($id);
        if(!$category){
            return $this->responseRedirectBack('Error occurred while deleting category','error',true,true);
        }
        $this->removeCache($category->slug);
        return $this->responseRedirect('admin.categories.index','Category deleted Successfully','success',false,false);
    }

    protected function removeCache($slug=null){
        Cache::forget('menu');
        Cache::forget('features');
        if($slug!=null) {
            Cache::forget('category_product_with_slug_'.$slug);
        }
    }
}
