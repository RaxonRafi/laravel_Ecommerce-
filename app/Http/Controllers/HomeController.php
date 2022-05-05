<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Color;
use App\Models\Size;
use App\Models\Team;
use Illuminate\Http\Request;
use App\Models\User;
use Carbon\Carbon;
use Faker\Provider\Image as ProviderImage;
use Illuminate\Support\Facades\Hash;
use Symfony\Contracts\Service\Attribute\Required;
use Illuminate\Support\Str;
use Image;


class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('checkrole');

    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $total_categories = Category::count();
        $total_users= User::count();
        return view('home',compact('total_categories','total_users'));
    }
    public function addteammember()
    {

        return view('team.addteammember');
    }
    public function teammemberinsert(Request $request){

    Team::insert([
        'member_name' => $request->team_member_name
    ]);
    return back()->with('success_message', 'Team member added successfully');
    }

    public function teammemberdelete($team_member_id)
    {

        Team::find($team_member_id)->delete();
        return back()->with('delete_message','Team member deleted successfully');
    }

    public function teammemberedit($team_member_id)
    {
      return view('team.editteammember',[
          'team_member_info'=>Team::find($team_member_id)
      ]);
    }

    public function teammemberupdate(Request $request, $team_member_id)
    {


     Team::find( $team_member_id)-> update([

        'member_name'=> $request->team_member_name
     ]);
      return back()->with('update_message', 'Team member updated successfully');

    }
    public function profile()
    {
     return view('profile.index');
    }

    public function changename(Request $request)
    {
        $request->validate([
                'name' => 'required',
                'phone_number' =>'digits:11'
        ]);
     User::find(auth()->id())->Update([
            'name'=>$request->name,
            'phone_number'=>$request->phone_number,
            'address'=>$request->address,
     ]);

     //photo upload code start

      if( $request->hasFile('profile_photo')){

        if(auth()->user()->profile_photo != 'default_profile_photo.jpg'){
            unlink( base_path("public/uploads/profile_photo/").auth()->user()->profile_photo);
        }
        //step:1-Profile photo name create Ex-(1.jpg)
        $new_name =auth()->id()."-".Str::random(5).".".$request->file('profile_photo')->getClientOriginalExtension();
        //step:2-Profile photo upload
       $save_link = base_path("public/uploads/profile_photo/").$new_name;
       Image::make($request->file('profile_photo'))->resize(300,300)->save($save_link);
       // step:3-Profile photo name update at database
        User::find(auth()->id())->update([
                'profile_photo'=>$new_name

        ]);
      }
     //photo upload code end

     return back()->with('status','Details Changed Succcessfully');
    }

    public function changepassword(Request $request)
    {
        $request->validate([
            'current_password'=>'required',
            'password'=>'required|confirmed|alpha_num|min:9',
            'password_confirmation'=>'required'
        ]);

     if($request->current_password == $request->password){
         return back()->with('current_password_err','Current password and New password Cannot be same');
     }
     if(Hash::check($request->current_password, auth()->user()->password)){
         User::find(auth()->id())->Update([
             'password' => bcrypt($request->password)
         ]);
         return back()->with('password_success','Password Changed Successfully!');
     }
     else{
    return back()->withErrors(['current_password_err'=>'Your Current Password is incorrect!']);
     }

    }
    public function variation()
    {
        return view('variation.index',[
            'colors' => Color::all(),
            'sizes' => Size::all()
        ]);
    }
    public function addcolor(Request $request)
    {
      Color::insert($request->except('_token') + ['created_at' => Carbon::now()]);
        return back();
    }
    public function addsize(Request $request)
    {
      Size::insert($request->except('_token') + ['created_at' => Carbon::now()]);
        return back();
    }

}
