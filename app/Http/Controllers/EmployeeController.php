<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\Employee;
use App\Models\Position;
use Illuminate\Support\Facades\Storage;
use illuminate\Support\Str;
use RealRashid\SweetAlert\Facades\Alert;

class EmployeeController extends Controller
{
    public function index()
    {
        $pageTitle = 'Employee List';
        
        confirmDelete();
        
        return view('employee.index', compact('pageTitle'));

        // // ELOQUENT
        // $employees = Employee::all();
        // return view('employee.index', [
        //     'pageTitle' => $pageTitle,
        //     'employees' => $employees
        // ]);
    }
    //     // RAW SQL QUERY
    // $employees = DB::select('
    // select *, employees.id as employee_id, positions.name as position_name
    // from employees
    // left join positions on employees.position_id = positions.id');
    // return view('employee.index', ['pageTitle' => $pageTitle, 'employees' => $employees]);


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $pageTitle = 'Create Employee'; // Inisialisasi data yang akan dilewatkan

        // ELOQUENT
        $positions = Position::all();

        return view('employee.create', compact('pageTitle', 'positions'));
    }
    // // RAW SQL Query
    // $positions = DB::select('select * from positions');

    // // //Query Builder
    // // $positions = DB::table('positions')->get();

    // return view('employee.create', compact('pageTitle', 'positions')); // Melakukan return view dengan data yang dilewatkan


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $messages = [
            'required' => ':Attribute harus diisi.',
            'email' => 'Isi :attribute dengan format yang benar',
            'numeric' => 'Isi :attribute dengan angka'
        ];

        $validator = Validator::make($request->all(), [
            'firstName' => 'required',
            'lastName' => 'required',
            'email' => 'required|email',
            'age' => 'required|numeric',
        ], $messages);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        $file = $request->file('cv');

        if ($file != null) {
            $originalFilename = $file->getClientOriginalName();
            $encryptedFilename = $file->hashName();

            // Store File
            $file->store('public/files');
        }

        // ELOQUENT
        $employee = new Employee;
        $employee->firstname = $request->firstName;
        $employee->lastname = $request->lastName;
        $employee->email = $request->email;
        $employee->age = $request->age;
        $employee->position_id = $request->position;

        if ($file != null) {
            $employee->original_filename = $originalFilename;
            $employee->encrypted_filename = $encryptedFilename;
        }

        $employee->save();

        Alert::success('Added Successfully', 'Employee Data Added Successfully.');

        return redirect()->route('employees.index');

        return redirect()->route('employees.index');
    }
    // // INSERT QUERY
    // DB::table('employees')->insert([
    //     'firstname' => $request->firstName,
    //     'lastname' => $request->lastName,
    //     'email' => $request->email,
    //     'age' => $request->age,
    //     'position_id' => $request->position,
    // ]);

    //  return redirect()->route('employees.index');



    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $pageTitle = 'Employee Detail';

        // ELOQUENT
        $employee = Employee::find($id);

        return view('employee.show', compact('pageTitle', 'employee'));
    }
    // // RAW SQL QUERY
    // $employee = collect(DB::select('
    //     select *, employees.id as employee_id, positions.name as position_name
    //     from employees
    //     left join positions on employees.position_id = positions.id
    //     where employees.id = ?
    // ', [$id]))->first();

    // //Query Builder
    // $employee = DB::table('employees')
    // ->leftJoin('positions', 'employees.position_id', '=', 'positions.id')
    // ->select('employees.*', 'employees.id as employee_id', 'positions.name as position_name')
    // ->where('employees.id', $id)
    // ->first();

    // return view('employee.show', compact('pageTitle', 'employee'));


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $pageTitle = 'Employee Edit';

        // ELOQUENT
        $positions = Position::all();
        $employee = Employee::find($id);

        return view('employee.edit', compact('pageTitle', 'employee', 'positions'));
    }


    public function update(Request $request, string $id)
    {
        $messages = [
            'required' => ':Attribute harus diisi.',
            'email' => 'Isi :attribute dengan format yang benar',
            'numeric' => 'Isi :attribute dengan angka'
        ];
        $validator = Validator::make($request->all(), [
            'firstName' => 'required',
            'lastName' => 'required',
            'email' => 'required|email',
            'age' => 'required|numeric',
        ], $messages);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // ELOQUENT
        $employee = Employee::find($id);
        $employee->firstname = $request->firstName;
        $employee->lastname = $request->lastName;
        $employee->email = $request->email;
        $employee->age = $request->age;
        $employee->position_id = $request->position;
        $employee->save();

        //handle cv 
        if ($request->hasFile('CV')){
        $file = $request->file('CV');
        $originalFilename = $file->getClientOriginalName();
        $encryptedFilename = $file->hashName();
        $file->store('public/files');

        //hapus cv lama jika tersedia 
        if ($employee->encrypted_filename){
            Storage::delete('public/files/' . $employee->encrypted_filename);
        }

        $employee->original_filename = $originalFilename;
        $employee->encrypted_filename = $encryptedFilename;
        }

        $employee->save();

        return redirect()->route('employees.index');
    }
    // // INSERT QUERY
    // DB::table('employees')->where('id', $id)->update([
    //     'firstname' => $request->firstName,
    //     'lastname' => $request->lastName,
    //     'email' => $request->email,
    //     'age' => $request->age,
    //     'position_id' => $request->position,
    // ]);


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // ELOQUENT
        $employee = Employee::find($id);

        //hapus cv
        if ($employee->encrypted_filename){
            Storage::delete('public/files/' . $employee->encrypted_filename);
        }

        $employee->delete();

        Alert::success('Deleted Successfully', 'Employee Data Deleted Successfully.');
        return redirect()->route('employees.index');
    }
    public function downloadFile($employeeId)
    {
            $employee = Employee::find($employeeId);
            $encryptedFilename = 'public/files/'.$employee->encrypted_filename;
            $downloadFilename = Str::lower($employee->firstname.'_'.$employee->lastname.'_cv.pdf');

            if(Storage::exists($encryptedFilename)) {
                return Storage::download($encryptedFilename, $downloadFilename);
        }
    }
    public function getData(Request $request)
    {
    $employees = Employee::with('position');

    if ($request->ajax()) {
        return datatables()->of($employees)
            ->addIndexColumn()
            ->addColumn('actions', function($employee) {
                return view('employee.actions', compact('employee'));
            })
            ->toJson();
        }
    }
}