<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Pengaduan;
use App\Models\User;
use App\Models\Kategori;
use JWTAuth;
use DB;

class PengaduanController extends Controller
{
    public $response;
    public $user;
    public function __construct(){
        $this->response = new ResponseHelper();

        $this->user = JWTAuth::parseToken()->authenticate();
    }

    public function getAllPengaduan($limit = NULL, $offset = NULL)
    {
        if($this->user->level == 'masyarakat'){
            $data["count"] = Pengaduan::where('id_user', '=', $this->user->id)->count();

            if($limit == NULL && $offset == NULL){
                $data["pengaduan"] = Pengaduan::where('id_user', '=', $this->user->id)->orderBy('tgl_pengaduan', 'desc')->with('kategori','tanggapan','user')->get();
            } else {
                $data["pengaduan"] = Pengaduan::where('id_user', '=', $this->user->id)->orderBy('tgl_pengaduan', 'desc')->with('kategori','tanggapan','user')->take($limit)->skip($offset)->get();
            }
        } else {
            $data["count"] = Pengaduan::count();

            if($limit == NULL && $offset == NULL){
                $data["pengaduan"] = Pengaduan::orderBy('tgl_pengaduan', 'desc')->with('kategori', 'tanggapan','user')->get();
            } else {
                $data["pengaduan"] = Pengaduan::orderBy('tgl_pengaduan', 'desc')->with('kategori', 'tanggapan','user')->take($limit)->skip($offset)->get();
            }
        }

        return $this->response->successData($data);
    }

    public function getById($id)
    {   
        $data["pengaduan"] = Pengaduan::where('id_pengaduan', $id)->with(['kategori','tanggapan', 'user'])->get();

        return $this->response->successData($data);
    }

    public function insert(Request $request)
    {
        $validator = Validator::make($request->all(), [
			'tgl_pengaduan' => 'required|date_format:Y-m-d',
			'isi_laporan' => 'required|string',
			'id_kategori' => 'required',
			'foto' => 'required',
		]);

		if($validator->fails()){
            return $this->response->errorResponse($validator->errors());
		}

        $foto = rand().$request->file('foto')->getClientOriginalName();
        $request->file('foto')->move(base_path("./public/uploads"), $foto);

		$pengaduan = new Pengaduan();
		$pengaduan->id_user         = $this->user->id;
		$pengaduan->id_kategori     = $request->id_kategori;
		$pengaduan->tgl_pengaduan   = $request->tgl_pengaduan;
		$pengaduan->isi_laporan     = $request->isi_laporan;
        $pengaduan->foto            = $foto;
        $pengaduan->status          = 'terkirim';
		$pengaduan->save();

        $data = Pengaduan::where('id_pengaduan','=', $pengaduan->id)->first();
        return $this->response->successResponseData('Data pengaduan berhasil terkirim', $data);
    }

    public function update(Request $request, $id)
    {
      try {
      	$validator = Validator::make($request->all(), [
			'tgl_pengaduan' => 'required|date_format:Y-m-d',
			'isi_laporan' => 'required|string',
			'id_kategori' => 'required',
			'foto'        => 'required',
			'status'        => 'required',

        ]);

      	if($validator->fails()){
      		return response()->json([
      			'status'	=> '0',
      			'message'	=> $validator->errors()
      		]);
      	}

      	//proses update data
      	$data = Pengaduan::where('id_pengaduan', $id)->first();
        $data->tgl_pengaduan = $request->input('tgl_pengaduan');
        $data->isi_laporan = $request->input('isi_laporan');
        $data->foto = $request->input('foto');
        $data->status = $request->input('status');
        $data->id_kategori = $request->input('id_kategori');
        $data->save();

      	return response()->json([
      		'status'	=> '1',
      		'message'	=> 'Data Pengaduan berhasil diubah'
      	]);
        
      } catch(\Exception $e){
          return response()->json([
              'status' => '0',
              'message' => $e->getMessage()
          ]);
      }
    }

    public function changeStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
			'id_pengaduan' => 'required',
			'status' => 'required|string',
		]);

		if($validator->fails()){
            return $this->response->errorResponse($validator->errors());
		}

		$pengaduan          = Pengaduan::where('id_pengaduan', $request->id_pengaduan)->first();
		$pengaduan->status  = $request->status;
		$pengaduan->save();

        return $this->response->successResponse('Status berhasil diubah');
    }

    public function report(Request $request)
    {
        $validator = Validator::make($request->all(), [
			'tahun' => 'required|numeric',
		]);

		if($validator->fails()){
            return $this->response->errorResponse($validator->errors());
		}

        $query = DB::table('pengaduan')
                    ->select('pengaduan.tgl_pengaduan', 'pengaduan.isi_laporan', 'pengaduan.status', 'kategori.nama_kategori', 'users.nama')
                    ->join('users', 'users.id', '=', 'pengaduan.id_user')
                    ->join('kategori', 'kategori.id_kategori', '=', 'pengaduan.id_kategori')
                    ->whereYear('pengaduan.tgl_pengaduan', '=', $request->tahun);

        if($request->bulan != NULL){
            $query->WhereMonth('pengaduan.tgl_pengaduan', '=', $request->bulan);
        }
        if($request->tgl != NULL){
            $query->WhereDay('pengaduan.tgl_pengaduan', '=', $request->tgl);
        }
        
        $data = $query->get();
        
        return $this->response->successData($data);
    }
    

}
