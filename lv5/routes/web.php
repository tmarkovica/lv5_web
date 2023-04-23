<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

//Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::get('/home', function() {
    if (Auth::check()) {
        if (Auth::user()->roles == 'admin') {
            $userRole = Auth::user()->roles;
            $tasks = null;

            if ($userRole == 'admin') {
                $tasks = DB::table('tasks')->get();
            }
            else if ($userRole == 'nastavnik') {
                $tasks = DB::table('tasks')->where("task_creator", "=", Auth::user()->id)->get();
            }

            return view('home', [
                'userRole' => $userRole,
                'tasks' => $tasks,
            ]);
        }
        else if (Auth::user()->roles == 'nastavnik') {

            $teachers_tasks = DB::table('tasks')->where("task_creator", "=", Auth::user()->id)->get();


            if (is_null($teachers_tasks) || empty($teachers_tasks))
                return view('nastavnik', [
                    'students' => []
                ]);


            $studentsArr = DB::select('SELECT * FROM get_students_on_teachers_tasks(?)', array(Auth::user()->id));
            //dd($students);
            //$students = DB::table('users')->get();
            $students = new Collection($studentsArr);

            /* //$allSelected = [];
            $allSelected = new Collection([]);
            foreach ($teachers_tasks as $task) {
                $selected = DB::table('selected_tasks')->where("task_id", "=", $task->id)->get();
                //$allSelected = array_merge($allSelected, [$selected]);
                $allSelected->push($selected);
            }

            //$students = []; // students that applied to this teachers tasks
            $students = new Collection([]);
            foreach ($allSelected as $selected) {
                $s = DB::table('users')->where("id", "=", $selected->user_id)->get();
                //$students = array_merge($students, [$s]);
                $students->push($s);
            } */

            return view('nastavnik', [
                'students' => $students,
                'teachers_tasks' => $teachers_tasks
            ]);
        }
        else if (Auth::user()->roles == 'student') {
            $userRole = Auth::user()->roles;
            $tasks = [];

            $tasks = DB::table('tasks')->get();

            $selectedTasksIndexes = [];

            $selectedTasks = DB::table('selected_tasks')->where('user_id', '=', Auth::user()->id)->get();
            foreach ($selectedTasks as $task) {
                $selectedTasksIndexes = array_merge($selectedTasksIndexes, [$task->task_id]);
            }


            return view('student', [
                'tasks' => $tasks,
                'selectedTasksIndexes' => $selectedTasksIndexes,
            ]);
        }
    }
});

Route::get('/new-task', function () {
    $users = DB::table('users')->get();

    return view('newtask', [
        'users' => $users
    ]);
});

Route::post('/create-task', function (Request $request) {

    $tip_studija = "";
    if ($request->tip_studija == 'value1')
        $tip_studija = "stručni";
    else if ($request->tip_studija == 'value2')
        $tip_studija = "preddiplomski";
    else if ($request->tip_studija == 'value3')
        $tip_studija = "diplomski";
    else
        return;

    DB::table('tasks')->insert([
        'naziv_rada' => $request->naziv_rada,
        'naziv_rada_eng' => $request->naziv_rada_eng,
        'zadatak_rada' => $request->zadatak_rada,
        'tip_studija' => $tip_studija,
        'task_creator' => Auth::user()->id
    ]);

    return redirect('/home');

})->name('create-task');


Route::post('/task-application/{taskId?}', function ($taskId = null) {

    DB::table('selected_tasks')->insert([
        'task_id' => $taskId,
        'user_id' => Auth::user()->id
    ]);

    return redirect('/home');

})->name('task-application');

Route::delete('/task-application/{taskId?}', function ($taskId = null) {

    DB::table('selected_tasks')->insert([
        'task_id' => $taskId,
        'user_id' => Auth::user()->id
    ]);

    return redirect('/home');

})->name('task-application');
