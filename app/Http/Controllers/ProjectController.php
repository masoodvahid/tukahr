<?php
namespace App\Http\Controllers;
use App\Models\Project;use App\Models\PayrollSheet;use Illuminate\Http\Request;
class ProjectController extends Controller {
 public function index(){return view('projects.index',['projects'=>Project::orderBy('name')->get()]);}
 public function store(Request $r){$d=$r->validate(['code'=>'required|string|max:50|unique:projects,code','name'=>'required|string|max:255']);Project::create($d+['created_by'=>$r->user()->id]);return back()->with('status','پروژه ایجاد شد.');}
 public function destroy(Project $project){if($project->assignments()->exists() || PayrollSheet::where('project_id',$project->id)->exists()){$project->update(['archived_at'=>now()]);return back()->with('status','پروژه بایگانی شد.');}$project->delete();return back()->with('status','پروژه حذف شد.');}
}
