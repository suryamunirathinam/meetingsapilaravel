<?php

namespace App\Http\Controllers;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Meeting;
class MeetingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function __construct(){
        $this->middleware('auth:api',['only'=> [
            'update','store','destroy'
        ]]);
    } 
    public function index()
    {
        $meetings = Meeting::all();
        foreach ($meetings as $meeting) {
            $meeting->view_meeting = [
                'href' => 'api/v1/meeting/' . $meeting->id,
                'method' => 'GET'
            ];
        }

        $response = [
            'msg' => 'List of all Meetings',
            'meetings' => $meetings
        ];
        return response()->json($response, 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
   
    public function store(Request $request)
    {   
        $validatedData = $request->validate([
            'title' => 'required',
            'description' => 'required',
            'time' => 'required|date_format:YmdHie',
            ]);
        if(!$user = auth()->user()){
            return response()->json(['msg'=>'User not found'],404);
        }

        $title = $request->input('title');
        $description = $request->input('description');
        $time = $request->input('time');
        $user_id = $user->id;
        
        $meeting = new Meeting([
            'time' => Carbon::createFromFormat('YmdHie',$time),
            'title' => $title,
            'description' => $description
        ]);
       
        if ($meeting->save()){
            $meeting->users()->attach($user_id);
            $meeting->view_meeting =[
                'href' => 'v1/meeting/'.$meeting->id,
                'method'=>'GET' 
            ];
            $message = [
                'msg' => 'Meeting Created',
                'meeting' => $meeting
            ];
            return response()->json($message,201);

        }
        // $meeting =[
        //     'title'=>$title,
        //     'description'=>$description,
        //     'time'=>$time,
        //     'user_id'=>$user_id,
        //     'view_meeting'=>[
        //         'href' => 'v1/meeting/1',    //id
        //         'method' =>'GET'
        //     ]

        // ];
        $response =[
            'msg' => 'Error Creating Metting',
            ];
        return response()->json($response,201); // successful and resourse was created
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // $meeting = Meeting::all();
        $meeting = Meeting::with('users')->where('id',$id)->firstOrFail();
        
        $meeting->view_meeting = [
            'href' => 'v1/meeting/'.$meeting->id,
            'method' => 'GET'
        ];
        
        $response =[
            'msg' => 'Meeting information',
            'meeting' => $meeting

        ];
        return response()->json($response,200); 
    }

    
    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'title'=>'required',
            'description'=>'required',
            'time'=>'required|date_format:YmdHie',
            
        ]);
        if(!$user = auth()->user()){
            return response()->json(['msg'=>'User not found'],404);
        }
        $title = $request->input('title');
        $description = $request->input('description');
        $time = $request->input('time');
        $user_id = $user->id;

        $meeting = Meeting::with('users')->findOrFail($id);

        if (!$meeting->users()->where('users.id', $user_id)->first()) {
            return response()->json(['msg' => 'user not registered for meeting, update not successful'], 401);
        };
        $meeting->time = Carbon::createFromFormat('YmdHie', $time);
        $meeting->title = $title;
        $meeting->description = $description;
        if (!$meeting->update()) {
            return response()->json(['msg' => 'Error during updating'], 404);
        }

        $meeting->view_meeting = [
            'href' => 'api/v1/meeting/' . $meeting->id,
            'method' => 'GET'
        ];
        $response =[
            'msg' => 'Meeting Updated',
            'meeting' =>$meeting
        ];
        return response()->json($response,200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $meeting = Meeting::findOrFail($id);
        if(!$user = auth()->user()){
            return response()->json(['msg'=>'User not found'],404);
        }
        if (!$meeting->users()->where('users.id', $user->id)->first()) {
            return response()->json(['msg' => 'user not registered for meeting, update not successful'], 401);
        };
        $users = $meeting->users;
        $meeting->users()->detach();
        if (!$meeting->delete()) {
            foreach ($users as $user) {
                $meeting->users()->attach($user);
            }
            return response()->json(['msg' => 'deletion failed'], 404);
        }

        $response = [
            'msg' => 'Meeting deleted',
            'create' => [
                'href' => 'api/v1/meeting',
                'method' => 'POST',
                'params' => 'title, description, time'
            ]
        ];

        return response()->json($response, 200);
    }
} 
