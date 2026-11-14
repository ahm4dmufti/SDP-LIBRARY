<?php

namespace App\Http\Controllers;

use App\Http\Requests\StaffStoreSEQ;
use App\Http\Requests\StaffUpdateREQ;
use App\Models\staff;
use App\Http\Resources\StaffRES;
use Illuminate\Http\Request;

class StaffCONT extends Controller
{
    public function index(Request $request)
    {
        $searchQuery = $request->input('search');
        $staff = \App\Models\staff::where('name', 'like', '%' . $searchQuery . '%')->get();
        return StaffRES::collection($staff);
    }

    public function store(StaffStoreSEQ $request)
    {
        $validatedData = $request->validated();
        $staff=staff::create($validatedData);

        return StaffRES::make($staff);
    }

    public function update(StaffUpdateREQ $request, $id)
    {
        $validatedData = $request->validated();
        $staff = staff::findOrFail($id);
        $staff->update($validatedData);
        return StaffRES::make($staff);


    }

    public function destroy($id)
    {
        $staff = staff::findOrFail($id);
        $staff->delete();
        return response()->json(['message' => 'Staff deleted successfully']);

    }

    public function show($id)
    {
        $staff = staff::findOrFail($id);
        return StaffRES::make($staff);

    } 


}
