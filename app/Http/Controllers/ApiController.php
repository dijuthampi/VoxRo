<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use DB;
use Illuminate\Contracts\Database\Eloquent\Builder;

class ApiController extends Controller
{
    //Function to load data 
    protected function loadFileData(){
        try{            
            $path = public_path('data/data.txt');
            $file_handle = fopen($path, 'r');
            $headers = [];
            while (!feof($file_handle)) {
                 $buffer = fgets($file_handle);
                 $cleanStr = str_replace("\r\n","",$buffer);
                 if (empty($headers)) {
                    $headers = explode(",",$cleanStr);
                 }else{
                    $data = explode(",",$cleanStr);
                    $productArr = [
                        "name" => $data[1],
                        "price" => $data[2]
                    ];
                    $product = Product::firstOrCreate($productArr);
                    $sales = new Sale([
                        "sale_day" => $data[0],
                        "quantity" => $data[3],
                        "total_price" => $data[4]
                    ]);
                    $product->sales()->save($sales);
                 }
            }
            return true;  
        }catch(Exception $e){
            return $e->message();
        }   
    }

    // To set up a demo user.Returns bearer token to access the API
    public function demoUser()
    {
        $user = User::create([
            'name' => "Admin",
            'email' => "admin@voxro.com",
            'email_verified_at' => now(),
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'remember_token' => Str::random(10),
        ]);
        $token = $user->createToken('auth_token')->plainTextToken;
        $data = [
            'name' => $user->name,
            'access_token' => $token,
            'token_type' => 'Bearer'
        ];
         $response = [
            'success' => true,
            'message' => "Successfully logged in!",
            'data' => $data
        ];
        return response()->json($response, 200);
    }

    public function dataload()
    {
        Schema::disableForeignKeyConstraints();
        Sale::truncate();
        Product::truncate();
        Schema::enableForeignKeyConstraints();
        $fileData = $this->loadFileData();
        if ($fileData === true) {
            $success = true;
            $message = "Data loaded successfully!";
            $data = "";
        }else{
            $success = false;
            $message = "Something went wrong!";
            $data = "";
        }
         $response = [
            'success' => $success,
            'message' => $message,
            'data' => $data
        ];
        return response()->json($response, 200);
    }

    public function createItem(Request $request)
    {
        $productArr = [
            "name" => $request->name,
            "price" => $request->price
        ];
        $product = Product::firstOrCreate($productArr);
        $sales = new Sale([
            "sale_day" => $request->sale_day,
            "quantity" => $request->quantity,
            "total_price" => $request->total_price
        ]);
        $saved = $product->sales()->save($sales);
        if ($saved) {
            $success = true;
            $message = "Item created successfully!";
            $data = "";
        }else{
            $success = false;
            $message = "Something went wrong!";
            $data = "";
        }
         $response = [
            'success' => $success,
            'message' => $message,
            'data' => $data
        ];
        return response()->json($response, 200);
    }
    public function totalSales(){
        $products = Product::select("id","name","price")
        ->withSum('sales','total_price')
        ->withSum('sales','quantity')
        ->get()
        ->toArray();
        $response = [
            'success' => true,
            'message' => "Data fetched successfully!",
            'data' => $products
        ];
        return response()->json($response, 200);
    }
    public function totalSalesByMonth(){
        $salesResult = Sale::select(
            DB::raw("(SUM(total_price)) as total_sale_price"),
            DB::raw("(SUM(quantity)) as total_sale_quantity"),
            DB::raw("MONTHNAME(sale_day) as month_name")
        )
        ->groupBy('month_name')
        ->get()
        ->toArray();
        $salesByMonth = [];
        foreach ($salesResult as $saleVal) {
            $salesByMonth[$saleVal["month_name"]] =  [
                "total_sale_price" => $saleVal["total_sale_price"],
                "total_sale_quantity" => $saleVal["total_sale_quantity"]
            ];
        }
        $response = [
            'success' => true,
            'message' => "Data fetched successfully!",
            'data' => $salesByMonth
        ];
        return response()->json($response, 200);
    }
    public function popularItemOfMonth(Request $request)
    {
        $month= $request->month ?? "January";
        $sales = Sale::select(
            "product_id",
            DB::raw("(SUM(quantity)) as total_sale_quantity"),
            DB::raw("MONTHNAME(sale_day) as month_name")
        )
        ->with("product")
        ->groupBy('month_name','product_id')
        ->where(DB::raw("MONTHNAME(sale_day)"),$month)
        ->orderBy("total_sale_quantity","DESC")
        ->get()
        ->first()
        ->toArray();
        $response = [
            'success' => true,
            'message' => "Data fetched successfully!",
            'data' => $sales
        ];
        return response()->json($response, 200);
    }
    public function mostRevenueByMonth(Request $request){
        $month = $request->month ?? "";
        $sales = Sale::select(
            "product_id",
            DB::raw("(SUM(total_price)) as total_sale_price"),
            DB::raw("MONTHNAME(sale_day) as month_name")
        )
        ->with("product")
        ->groupBy('month_name','product_id')
        ->orderByRaw("month_name DESC , total_sale_price DESC")
        ->get();
        $months = $sales->pluck("month_name");
        $months = $months->toArray();
        $months = array_unique($months);
        $resultSet = [];
        foreach ($months as $monthVal) {
            $resultSet[$monthVal] = $sales->firstWhere('month_name', $monthVal)->toArray();
        } 
        if (in_array($month,$months)) {
            $result = $resultSet[$month];
        }else{
            $result = $resultSet;
        }       
        $response = [
            'success' => true,
            'message' => "Data fetched successfully!",
            'data' => $result
        ];
        return response()->json($response, 200);
    }
}
