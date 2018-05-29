<?php
require APPPATH . '/libraries/REST_Controller.php';

class Guru extends REST_Controller {
	// show 
	// function index_get() {
		
	// 	$this->db->select('guru.*,detail_guru.tarif,detail_guru.id_detail');
	// 	$this->db->from('guru');
	// 	$this->db->join('detail_guru', 'guru.id_guru = detail_guru.id_guru','left');
	// 	$guru= $this->db->get()->result();
	// 	$this->response(array("status"=>"success","result" => $guru));
	// }
	function index_get() {
			$id_guru = $this->get('id_guru');
			$this->db->where('guru.status', '1');
		    if($id_guru <> ''){ //byID
		        $this->db->where('guru.id_guru', $id_guru);
        		$guru= $this->db->get('guru')->result();
        		$data_guru = [];
        		foreach ($guru as $key => $value) {
        			$v = json_decode(json_encode($value),TRUE);
        			$where = "detail_guru.id_guru = ".$value->id_guru;
        			$id_detail = $this->db->get('detail_guru')->result();
					$id_detail = json_decode(json_encode($id_detail),true);
					$id_detail = array_column($id_detail, "id_detail");
					if(count($id_guru) > 0){
						$where_les = "id_detail IN ( ".implode(',',$id_detail).")";
					}else{
						$where_les = "id_detail = 0";
					}
					$rating =0;
					$jml = 0;
					$this->db->where($where_les);
					$les = $this->db->get('les')->result();
					if(count($les) > 0){
						foreach ($les as $key2 => $value2) {
							$rating += $value2->rating;
							$jml++;
						}
					}
					$v['rating_baru'] = ($rating > 0 && $jml > 0 ? $rating/$jml : 0);
					$data_guru[] = $v;
        		}
        		$data_guru = json_decode(json_encode($data_guru),FALSE);
        		$this->response(array("status"=>"success","result" => $data_guru));
	        } else {
        		$guru= $this->db->get('guru')->result();
        		$data_guru = [];
        		foreach ($guru as $key => $value) {
        			$v = json_decode(json_encode($value),TRUE);
        			$where = "detail_guru.id_guru = ".$value->id_guru;
        			$this->db->where($where);
        			$id_detail = $this->db->get('detail_guru')->result();
					$id_detail = json_decode(json_encode($id_detail),true);
					$id_detail = array_column($id_detail, "id_detail");
					if(count($id_detail) > 0){
						$where_les = "id_detail IN ( ".implode(',',$id_detail).")";
					}else{
						$where_les = "id_detail = 0";
					}
					$rating =0;
					$jml = 0;
					$this->db->where($where_les);
					$les = $this->db->get('les')->result();
					if(count($les) > 0){
						foreach ($les as $key2 => $value2) {
							$rating += $value2->rating;
							$jml++;
						}
					}
					$v['rating_baru'] = round(($rating > 0 && $jml > 0 ? $rating/$jml : 0),2);
					$v['jml'] = $jml;
					$v['rat'] = $rating;
					$data_guru[] = $v;
        		}
        		$data_guru = json_decode(json_encode($data_guru),FALSE);
        		$this->response(array("status"=>"success","result" => $data_guru));
		}
	}
		// delete 
	function delTarif_delete() {
		$id_detail = $this->delete('id_detail');

		if (empty($id_detail)){
			$this->response(array('status' => 'fail', "message"=>"id_detail harus diisi"));
		}else{
			$this->db->where('id_detail', $id_detail);
			$delete = $this->db->delete('detail_guru');
			
			if ($this->db->affected_rows()) {
				$this->response(array('status' => 'success','message' =>"Berhasil delete dengan id_detail = ".$id_detail));
			} else{
			$this->response(array('status' => 'fail', 'message' =>"id_detail tidak dalam database"));
			}
		}
	}
	function filter_get() {
		$where = "";
		if(isset($_GET['mapel'])){
			$mapel = $this->get('mapel');
			$this->db->where('id_mapel',$mapel);
			$this->db->group_by('id_guru');
			$id_guru = $this->db->get('detail_guru')->result();
			$id_guru = json_decode(json_encode($id_guru),true);
			$id_guru = array_column($id_guru, "id_guru");
			if(count($id_guru) > 0){
				$where .= "id_guru IN ( ".implode(',',$id_guru).")";
			}else{
				$where .= "id_guru = 0";
			}
		}
		if(isset($_GET['mapel'])){
			$mapel = $this->get('mapel');
			$this->db->where('id_mapel',$mapel);
			$this->db->group_by('id_guru');
			$id_guru = $this->db->get('detail_guru')->result();
			$id_guru = json_decode(json_encode($id_guru),true);
			$id_guru = array_column($id_guru, "id_guru");
			if(count($id_guru) > 0){
				if($where == ""){
				$where .= "id_guru IN ( ".implode(',',$id_guru).")";
			}else{
				$where  .= "AND id_guru IN ( ".implode(',',$id_guru).")";
			}
				
			}
		}

		if(isset($_GET['jk'])){
			$jk = $this->get('jk');
			$jk2 = ["","L","P"];
			if($where == ""){
				$where  .= "jk ='".$jk2[$jk]."'";
			}else{
				$where  .= "AND jk ='".$jk2[$jk]."'";
			}
		}
		// $jk = $this->get('jk');

		if(isset($_GET['rating'])){
			$rating = $this->get('rating');
			if($rating == '1')
			{
				$this->db->order_by('rating','ASC');
			}
			else{
				$this->db->order_by('rating','DESC');	
			}
		}

		if(isset($_GET['search'])){
			$search = $this->get('search');
			$search = urldecode($search);
			if($where == ""){
				$where  .= "nama LIKE '%".$search."%'";
			}else{
				$where  .= "AND nama LIKE '%".$search."%'";
			}
		}

		// $jk = $this->get('jk');
		if($where != ""){
		$this->db->where($where);
		}
		$guru = $this->db->get('guru');
		$data = $guru->result();
		$data_guru = [];
        		foreach ($data as $key => $value) {
        			$v = json_decode(json_encode($value),TRUE);
        			$where = "detail_guru.id_guru = ".$value->id_guru;
        			$this->db->where($where);
        			$id_detail = $this->db->get('detail_guru')->result();
					$id_detail = json_decode(json_encode($id_detail),true);
					$id_detail = array_column($id_detail, "id_detail");
					if(count($id_detail) > 0){
						$where_les = "id_detail IN ( ".implode(',',$id_detail).")";
					}else{
						$where_les = "id_detail = 0";
					}
					$rating =0;
					$jml = 0;
					$this->db->where($where_les);
					$les = $this->db->get('les')->result();
					if(count($les) > 0){
						foreach ($les as $key2 => $value2) {
							$rating += $value2->rating;
							$jml++;
						}
					}
					$v['rating_baru'] =round( ($rating > 0 && $jml > 0 ? $rating/$jml : 0),2);
					$v['jml'] = $jml;
					$v['rat'] = $rating;
					$data_guru[] = $v;
        		}
        		$data_guru = json_decode(json_encode($data_guru),FALSE);
		$this->response(array("status"=>"success","result" => $data_guru));
	}
	

	function rating_post() {
		$id_les = $this->post('id_les');
		$data = array(
			'rating' => $this->post('rating')
			);
		$this->db->where('id_les', $id_les);
		$update = $this->db->update('les', $data);
		if ($update) {
			$this->response(array('status' => 'success','message' =>"Berhasil update dengan id_les = ".$id_les));
			$this->response($data, 200);
		} else {
			$this->response(array('status' => 'fail', 'message' =>"id_les tidak dalam database"));
			//$this->response(array('status' => 'fail', 502));
		}
	}
function byID_get()
	{
		$id_guru = $this->get('id_guru');

		    if($id_guru <> ''){
		        $this->db->select('guru.*, substr(video, -11) as "vid" ');
		        $this->db->where('guru.id_guru', $id_guru);
    		  	//$this->db->join('detail_guru', 'guru.id_guru = detail_guru.id_guru','left');
        		
        		$guru= $this->db->get('guru')->result();
        		$this->response(array("status"=>"success","result" => $guru));
		
	        } else {
    		    //$this->db->join('detail_guru', 'guru.id_guru = detail_guru.id_guru','left');
        		$guru= $this->db->get('guru')->result();
        		$this->response(array("status"=>"success","result" => $guru));
    	
		}
	}
	
	function dataOrder_post()
	{
		$data['id_detail'] = $this->post('id_detail');
		$data['id_siswa'] = $this->post('id_siswa');
		$data['pertemuan'] = $this->post('pertemuan');
		$data['tanggal_les'] = $this->post('tanggal_les');
		$data['jam'] = $this->post('jam');
		$data['status'] = "wait";
		$jadwal_hari = $this->post('jadwal_hari');
		$insert= $this->db->insert('les',$data);
		$insert_id = $this->db->insert_id();
		$jadwal_hari = explode(",", $jadwal_hari);
		$i= 0;
		$data_jadwal = [];
		foreach ($jadwal_hari as $key => $value) {
			$jadwal = explode("|", $value);
			$data2 = [
				"id_jadwal" => $jadwal[2],
				"tanggal" => $jadwal[0],
				"id_les" => $insert_id,
				"status" => "wait",
				"rating" => 0
			];
			$insert= $this->db->insert('detail_les',$data2);
			$i++;
		}
		$this->response(array('status' => 'success','message' =>"Berhasil insert"));
			$this->response($data, 200);
	}


	function histori_get()
	{
		$this->db->select('*');
		$this->db->from('detail_guru');
		$this->db->join('guru', 'guru.id_guru = detail_guru.id_guru');
		$this->db->join('les', 'les.id_detail = detail_guru.id_detail');
		$histori = $this->db->get()->result();
		$this->response(array("status"=>"success","result" => $histori));
	}


	// insert
	function index_post() {
		$data['email'] = $this->post('email');
		$data['password'] = $this->post('password');
		$data['nama'] = $this->post('nama');
		$data['alamat'] = $this->post('alamat');
		$data['no_telp'] = $this->post('no_telp');
		$data['foto'] = $this->post('foto');
		$data['latitude'] = $this->post('latitude');
		$data['longitude'] = $this->post('longitude');
			//Validasi input data
		if (empty($data['email'])) {
			$this->response(array('status' => "fail", "message"=>"email harus diisi"));
		} else if (empty($data['password'])) {
			$this->response(array('status' => "fail", "message"=>"Password harus diisi"));
		} else if (empty($data['latitude'])) {
			$this->response(array('status' => "fail", "message"=>"latitude harus diisi"));
		}
		else if (empty($data['longitude'])) {
			$this->response(array('status' => "fail", "message"=>"longitude harus diisi"));
		}else if (empty($data['nama'])) {
			$this->response(array('status' => "fail", "message"=>"Nama harus diisi"));
		} else if (empty($data['alamat'])) {
			$this->response(array('status' => "fail", "message"=>"Alamat harus diisi"));
		} else if (empty($data['no_telp'])) {
			$this->response(array('status' => "fail", "message"=>"Nomor HP harus diisi"));
		} else {
			//username check
			$check = $this->db->query("SELECT * FROM guru where email='".$this->input->post('email')."'")->num_rows();
			if ($check <= 0) {
				$this->insert_guru($data);
			} else {
				$this->response(array('status' => 'fail','message' =>"username sudah digunakan"));
				$this->response($data, 200);
			}
		}
	}//end index_post
	
	function insert_guru($data){
		//function upload image
		$uploaddir = str_replace("application/", "", APPPATH).'upload/';
		if(!file_exists($uploaddir) && !is_dir($uploaddir)) {
			echo mkdir($uploaddir, 0755, true);
		}
		if (!empty($_FILES)){
			$path = $_FILES['foto']['name'];
			$ext = pathinfo($path, PATHINFO_EXTENSION);
			$user_img = $data['nama']. '.' . "png";
			$uploadfile = $uploaddir . $user_img;
			$data['foto'] = "upload/".$user_img;
		}else{
			$data['foto']="";
		}

		$get_guru_baseid = $this->db->query("SELECT * FROM guru as p WHERE
			p.id_guru='".$data['nama']."'")->result();
		if(empty($get_guru_baseid)){
			$insert= $this->db->insert('guru',$data);
			if (!empty($_FILES)){
				if ($_FILES["foto"]["name"]) {
					if
						(move_uploaded_file($_FILES["foto"]["tmp_name"],$uploadfile))
					{
						$insert_image = "success";
					} else{
						$insert_image = "failed";
					}
				}else{
					$insert_image = "Image Tidak ada Masukan";
				}
				$data['foto'] = base_url()."upload/".$user_img;
			}else{
				$data['foto'] = "";
			}
			if ($insert){
				$this->response(array('status'=>'success','message' => 'Berhasil Upload'));
			}
		}else{
			$this->response(array('status' => "failed", "message"=>"Id_guru
				sudah ada"));
		}	
	} //end insert guru

	// login
	function login_post() {
		$data['email'] = $this->post('email');
		$data['password'] = $this->post('password');
		
		if (empty($data['email'])) {
			$this->response(array('status' => "fail", "message"=>"email harus diisi"));

		} else if (empty($data['password'])) {
			$this->response(array('status' => "fail", "message"=>"Password harus diisi"));

		} else{
			$email = $data['email'];
			$password = $data['password'];

			$cek_login = "select * from guru where email=? limit 1";
			$cek_email = $this->db->query($cek_login,$email);
			$result_login = $cek_email->row();
			
			if ($this->db->affected_rows()==1) {
				$pass = $result_login->password;
				if ($password == $pass) {
					$get_status = $result_login->status;
					if ($get_status == 1) {
						//login berhasil
						$this->response(array('status' => "success", "message"=>$result_login->id_guru));
					} else {
						//belum verifikasi
						$this->response(array('status' => "fail", "message"=>"belum melakukan verifikasi"));
					}	
				}else {
					//password salah
					$this->response(array('status' => "fail", "message"=>"Password Salah"));
				}
			} else {
				$this->response(array('status' => "fail", "message"=>"Email Belum Terdaftar"));
			}
		}	

	}

	// update 
	function index_put() {
		$id_guru = $this->put('id_guru');
		$data = array(
			'nama' => $this->put('nama'),
			'alamat' => $this->put('alamat'),
			'email' => $this->put('email'),
			'password' => $this->put('password'),
			'jk' => $this->put('jk'),
			'pendidikan' => $this->put('pendidikan'),
			'no_telp' => $this->put('no_telp')
			);
		
		$this->db->where('id_guru', $id_guru);
		$update = $this->db->update('guru', $data);
		if ($update) {
			$this->response(array('status' => 'success','message' =>"Berhasil update dengan id_guru = ".$id_guru));
			$this->response($data, 200);
		} else {
			$this->response(array('status' => 'fail', 'message' =>"id_guru tidak dalam database"));
			//$this->response(array('status' => 'fail', 502));
		}
	}
	// update 
	function pengalaman_put() {
		$id_guru = $this->put('id_guru');
		$data = array(
			'pengalaman' => $this->put('pengalaman'),
			'video' => $this->put('video')
			);
		
		$this->db->where('id_guru', $id_guru);
		$update = $this->db->update('guru', $data);
		if ($update) {
			$this->response(array('status' => 'success','message' =>"Berhasil update dengan id_guru = ".$id_guru));
			$this->response($data, 200);
		} else {
			$this->response(array('status' => 'fail', 'message' =>"id_guru tidak dalam database"));
			//$this->response(array('status' => 'fail', 502));
		}
	}
	function tarif_post()
	{
		$data['tarif'] = $this->post('tarif');
		$data['id_guru'] = $this->post('id_guru');
		$data['id_jenjang'] = $this->post('id_jenjang');
		$data['id_mapel'] = $this->post('id_mapel');
		$data['id_kelas'] = $this->post('id_kelas');
			//Validasi input data
		
		$update = $this->db->insert('detail_guru', $data);
		if ($update) {
			$this->response(array('status' => 'success','message' =>"Berhasil insert"));
			$this->response($data, 200);
		} else {
			$this->response(array('status' => 'fail', 'message' =>"id_guru tidak dalam database"));
			//$this->response(array('status' => 'fail', 502));
		}
	}
	
	// update 
	function verY_put() {
		$id_les = $this->put('id_les');
		$data['status'] = "terima";
		$this->db->where('id_les', $id_les);
		$update = $this->db->update('les', $data);
		if ($update) {
			$this->response(array('status' => 'success','message' =>"Berhasil update dengan id_les = ".$id_les));
			$this->response($data, 200);
		} else {
			$this->response(array('status' => 'fail', 'message' =>"id_les tidak dalam database"));
			//$this->response(array('status' => 'fail', 502));
		}
	}
		// update 
	function verN_put() {
		$id_les = $this->put('id_les');
		$data['status'] = "tolak";
		$this->db->where('id_les', $id_les);
		$update = $this->db->update('les', $data);
		if ($update) {
			$this->response(array('status' => 'success','message' =>"Berhasil update dengan id_les = ".$id_les));
			$this->response($data, 200);
		} else {
			$this->response(array('status' => 'fail', 'message' =>"id_les tidak dalam database"));
			//$this->response(array('status' => 'fail', 502));
		}
	}
	
	// update 
	function cancelSiswa_put() {
		$id_les = $this->put('id_les');
		$data['status'] = "cancelsiswa";
		$this->db->where('id_les', $id_les);
		$update = $this->db->update('les', $data);
		if ($update) {
			$this->response(array('status' => 'success','message' =>"Berhasil update dengan id_les = ".$id_les));
			$this->response($data, 200);
		} else {
			$this->response(array('status' => 'fail', 'message' =>"id_les tidak dalam database"));
			//$this->response(array('status' => 'fail', 502));
		}
	}

	// update 
	function cancelGuru_put() {
		$id_les = $this->put('id_les');
		$data['status'] = "cancel";
		$this->db->where('id_les', $id_les);
		$update = $this->db->update('les', $data);
		if ($update) {
			$this->response(array('status' => 'success','message' =>"Berhasil update dengan id_les = ".$id_les));
			$this->response($data, 200);
		} else {
			$this->response(array('status' => 'fail', 'message' =>"id_les tidak dalam database"));
			//$this->response(array('status' => 'fail', 502));
		}
	}

	// update 
	function statusDone_put() {
		$id_les = $this->put('id_les');
		$data['status'] = "done";
		$this->db->where('id_les', $id_les);
		$update = $this->db->update('les', $data);
		if ($update) {
			$this->response(array('status' => 'success','message' =>"Berhasil update dengan id_les = ".$id_les));
			$this->response($data, 200);
		} else {
			$this->response(array('status' => 'fail', 'message' =>"id_les tidak dalam database"));
			//$this->response(array('status' => 'fail', 502));
		}
	}
	
	//list orderan untuk guru
	function listOrderGuru_get()
	{
		$id_guru = $this->get('id_guru');
		$id_les = $this->get('id_les');
		if($id_guru <> '' && $id_les <> ''){
			//tampil DETAIL
		    $this->db->select("guru.*, les.*, detail_guru.*, siswa.nama as 'nama_siswa', siswa.alamat as 'alamat_siswa', siswa.email as 'email_siswa', siswa.foto as 'foto_siswa', siswa.no_telp as 'no_telp_siswa', siswa.jk as 'jk_siswa' ");
			$this->db->join('siswa', 'siswa.id_siswa = les.id_siswa');
			$this->db->join('detail_guru', 'detail_guru.id_detail = les.id_detail');
			$this->db->join('guru', 'detail_guru.id_guru = guru.id_guru', 'left');
			$this->db->where('les.status', 'wait');
			$this->db->where('detail_guru.id_guru', $id_guru);
			$this->db->where('id_les', $id_les);
			$query = $this->db->get('les')->result();
			$this->response(array("status"=>"success","result" => $query));
		}
		elseif($id_guru <> '' ){
			//tampil LIST ORDER
			$this->db->select("guru.*, les.*, detail_guru.*, siswa.nama as 'nama_siswa', siswa.alamat as 'alamat_siswa', siswa.email as 'email_siswa', siswa.foto as 'foto_siswa', siswa.no_telp as 'no_telp_siswa', siswa.jk as 'jk_siswa' ");
			$this->db->join('siswa', 'siswa.id_siswa = les.id_siswa');
			$this->db->join('detail_guru', 'detail_guru.id_detail = les.id_detail');
			$this->db->join('guru', 'detail_guru.id_guru = guru.id_guru', 'left');
			$this->db->where('les.status', 'wait');
			$this->db->where('detail_guru.id_guru', $id_guru);
			$query = $this->db->get('les')->result();
			$this->response(array("status"=>"success","result" => $query));			
		}
		else {
    		$this->response(array('status' => "fail", "message"=>"cek ID "));
		}
	}
	
	function concatDetail_get()
	{
		$id_guru = $this->get('id_guru');
		if ($id_guru<> '') {
			$this->db->where('id_guru', $id_guru);
			$this->db->select('concat_ws(" ",jenjang.nama_jenjang, "kelas", kelas.nama_kelas,mapel.nama_mapel, "Rp.", tarif) as "concatTarif" ');
			$this->db->join('jenjang', 'detail_guru.id_jenjang=jenjang.id_jenjang');
			$this->db->join('kelas', 'detail_guru.id_kelas=kelas.id_kelas');
			$this->db->join('mapel', 'detail_guru.id_mapel=mapel.id_mapel');
			$db = $this->db->get('detail_guru')->result();
		} else {
			$this->db->select('concat_ws(" ",jenjang.nama_jenjang, "kelas", kelas.nama_kelas,mapel.nama_mapel, "Rp.", tarif) as "concatTarif"');
			$this->db->join('jenjang', 'detail_guru.id_jenjang=jenjang.id_jenjang');
			$this->db->join('kelas', 'detail_guru.id_kelas=kelas.id_kelas');
			$this->db->join('mapel', 'detail_guru.id_mapel=mapel.id_mapel');
			$db = $this->db->get('detail_guru')->result();
		}
		$this->response(array("status"=>"success","result" => $db));
	}
	function concatDetailGuru_get()
	{
		$id_guru = $this->get('id_guru');
		if ($id_guru<> '') {
			$this->db->where('id_guru', $id_guru);
			$this->db->select('concat_ws(" ",jenjang.nama_jenjang, "kelas", kelas.nama_kelas,mapel.nama_mapel, "Rp.", tarif) as "concatTarif",id_guru,id_detail,tarif');
			$this->db->join('jenjang', 'detail_guru.id_jenjang=jenjang.id_jenjang');
			$this->db->join('kelas', 'detail_guru.id_kelas=kelas.id_kelas');
			$this->db->join('mapel', 'detail_guru.id_mapel=mapel.id_mapel');
			$db = $this->db->get('detail_guru')->result();
		} else {
			$this->db->select('concat_ws(" ",jenjang.nama_jenjang, "kelas", kelas.nama_kelas,mapel.nama_mapel, "Rp.", tarif) as "concatTarif",id_guru,id_detail,tarif');
			$this->db->join('jenjang', 'detail_guru.id_jenjang=jenjang.id_jenjang');
			$this->db->join('kelas', 'detail_guru.id_kelas=kelas.id_kelas');
			$this->db->join('mapel', 'detail_guru.id_mapel=mapel.id_mapel');
			$db = $this->db->get('detail_guru')->result();
		}
		$this->response(array("status"=>"success","result" => $db));
	}
	
	//lupa password
	function lupa_post()
	{
		$data['email'] = $this->post('email');
			//Validasi input data
		if (empty($data['email'])) {
			$this->response(array('status' => "fail", "message"=>"email harus diisi"));
		} else {
			
			$q=	$this->db->query("SELECT password FROM guru where email='".$this->input->post('email')."'")->row();
			$pass=$q->password;
			if ($q) {
				
				$to = $data['email'];
				$subject = "lupa password";
				$txt = "password anda adalah ".$pass;
				$headers = "From: goprimalang@gmail.com" . "\r\n" .
				"CC: goprimalang@gmail.com";

				mail($to,$subject,$txt,$headers);

				$this->response(array('status' => "success", "message"=>"Berhasil "));
			} else {
				$this->response(array('status' => "fail", "message"=>"gagal "));
			}	
		}
	}//end lupa
	
	function Terima_get()
	{
	    $id_guru = $this->get('id_guru');
		$id_les = $this->get('id_les');
		if($id_guru <> '' && $id_les <> ''){
			//tampil terima -> DETAIL
		    $this->db->select("guru.*, les.*, detail_guru.*, siswa.nama as 'nama_siswa', siswa.alamat as 'alamat_siswa', siswa.email as 'email_siswa', siswa.foto as 'foto_siswa', siswa.no_telp as 'no_telp_siswa', siswa.jk as 'jk_siswa' ");
			$this->db->join('siswa', 'siswa.id_siswa = les.id_siswa');
			$this->db->join('detail_guru', 'detail_guru.id_detail = les.id_detail');
			$this->db->join('guru', 'detail_guru.id_guru = guru.id_guru', 'left');
			$this->db->where('les.status', 'terima');
			$this->db->where('detail_guru.id_guru', $id_guru);
			$this->db->where('id_les', $id_les);
			$query = $this->db->get('les')->result();
			$this->response(array("status"=>"success","result" => $query));
		}
		elseif($id_guru <> '' ){
			//LIST
			$this->db->select("guru.*, les.*, detail_guru.*, siswa.nama as 'nama_siswa', siswa.alamat as 'alamat_siswa', siswa.email as 'email_siswa', siswa.foto as 'foto_siswa', siswa.no_telp as 'no_telp_siswa', siswa.jk as 'jk_siswa' ");
			$this->db->join('siswa', 'siswa.id_siswa = les.id_siswa');
			$this->db->join('detail_guru', 'detail_guru.id_detail = les.id_detail');
			$this->db->join('guru', 'detail_guru.id_guru = guru.id_guru', 'left');
			$this->db->where('les.status', 'terima');
			$this->db->where('detail_guru.id_guru', $id_guru);
			$query = $this->db->get('les')->result();
			$this->response(array("status"=>"success","result" => $query));			
		}
		else {
    		//cek pemanggilan id di android
    		$this->response(array('status' => "fail", "message"=>"coba cek rest"));
		}
	}//end terima
	
	/* done, cancel, done -> byID menggunakan fungsi byHis */
	
	function Done_get() //hanya untuk menampilkan histori yang selesai
	{
	    $id_guru = $this->get('id_guru');
		$id_les = $this->get('id_les');
		if($id_guru <> ''){
		    //LIST
			$this->db->select("guru.*, les.*, detail_guru.*, siswa.nama as 'nama_siswa', siswa.alamat as 'alamat_siswa', siswa.email as 'email_siswa', siswa.foto as 'foto_siswa', siswa.no_telp as 'no_telp_siswa', siswa.jk as 'jk_siswa' ");
			$this->db->join('siswa', 'siswa.id_siswa = les.id_siswa');
			$this->db->join('detail_guru', 'detail_guru.id_detail = les.id_detail');
			$this->db->join('guru', 'detail_guru.id_guru = guru.id_guru', 'left');
			$this->db->where('les.status', 'done');
			$this->db->where('detail_guru.id_guru', $id_guru);
			$query = $this->db->get('les')->result();
			$this->response(array("status"=>"success","result" => $query));
		}else {
    		//cek pemanggilan id di android
    		$this->response(array('status' => "fail", "message"=>"coba cek rest"));
		}
	}
	
	function Cancel_get()//hanya untuk menampilkan histori yang Cancel
	{
	    $id_guru = $this->get('id_guru');
		$id_les = $this->get('id_les');
		if($id_guru <> ''){
		//tampil LIST
		    $this->db->select("guru.*, les.*, detail_guru.*, siswa.nama as 'nama_siswa', siswa.alamat as 'alamat_siswa', siswa.email as 'email_siswa', siswa.foto as 'foto_siswa', siswa.no_telp as 'no_telp_siswa', siswa.jk as 'jk_siswa' ");
			$this->db->join('siswa', 'siswa.id_siswa = les.id_siswa');
			$this->db->join('detail_guru', 'detail_guru.id_detail = les.id_detail');
			$this->db->join('guru', 'detail_guru.id_guru = guru.id_guru', 'left');
			$this->db->where('les.status', 'cancel');
			$this->db->where('detail_guru.id_guru', $id_guru);
			$query = $this->db->get('les')->result();
			$this->response(array("status"=>"success","result" => $query));
		}else {
    	//cek pemanggilan id di android
    		$this->response(array('status' => "fail", "message"=>"coba cek rest"));
		}
	}//end cancel
	
	function Tolak_get()//hanya untuk menampilkan histori yang Tolak
	{
	    $id_guru = $this->get('id_guru');
		$id_les = $this->get('id_les');
		if($id_guru <> '' ){
		    //LIST
		    $this->db->select("guru.*, les.*, detail_guru.*, siswa.nama as 'nama_siswa', siswa.alamat as 'alamat_siswa', siswa.email as 'email_siswa', siswa.foto as 'foto_siswa', siswa.no_telp as 'no_telp_siswa', siswa.jk as 'jk_siswa' ");
			$this->db->join('siswa', 'siswa.id_siswa = les.id_siswa');
			$this->db->join('detail_guru', 'detail_guru.id_detail = les.id_detail');
			$this->db->join('guru', 'detail_guru.id_guru = guru.id_guru', 'left');
			$this->db->where('les.status', 'tolak');
			$this->db->where('detail_guru.id_guru', $id_guru);
			$query = $this->db->get('les')->result();
			$this->response(array("status"=>"success","result" => $query));
		}else {
		    //cek pemanggilan id di android
    		$this->response(array('status' => "fail", "message"=>"coba cek rest"));
		}
	}//end tolak

	 
	function byHis_get()//fungsi untuk ByID done,cancel,tolak
	{
		$id_guru = $this->get('id_guru');
		$id_les = $this->get('id_les');
		if($id_guru <> '' && $id_les <> ''){
			//tampil DETAIL
			//tanpa melihat status
		    $this->db->select("guru.*, les.*, detail_guru.*, siswa.nama as 'nama_siswa', siswa.alamat as 'alamat_siswa', siswa.email as 'email_siswa', siswa.foto as 'foto_siswa', siswa.no_telp as 'no_telp_siswa', siswa.jk as 'jk_siswa' ");
			$this->db->join('siswa', 'siswa.id_siswa = les.id_siswa');
			$this->db->join('detail_guru', 'detail_guru.id_detail = les.id_detail');
			$this->db->join('guru', 'detail_guru.id_guru = guru.id_guru', 'left');
			$this->db->where('detail_guru.id_guru', $id_guru);
			$this->db->where('id_les', $id_les);
			$query = $this->db->get('les')->result();
			$this->response(array("status"=>"success","result" => $query));
		}	else {
    		$this->response(array('status' => "fail", "message"=>"coba cek rest"));
		}
	}//end list

	function foto_post(){
		$data_pembeli = array(
			'id_guru' => $this->post('id_guru'),
			'foto' => $this->post('foto')
		);
		$this->updatePembeli($data_pembeli);
	}

	function updatePembeli($data_pembeli){
	//function upload image
		$uploaddir = str_replace("application/", "", APPPATH).'upload/';
			if(!file_exists($uploaddir) && !is_dir($uploaddir)) {
				echo mkdir($uploaddir, 0750, true);
			}
			if(!empty($_FILES)){
				$path = $_FILES['foto']['name'];
				// $ext = pathinfo($path, PATHINFO_EXTENSION);
				//$user_img = time() . rand() . '.' . $ext;
				$user_img = $data_pembeli['id_guru'].'.' ."png";
				$uploadfile = $uploaddir . $user_img;
				$data_pembeli['foto'] = "upload/".$user_img;
			}
			
				$get_pembeli_baseid = $this->db->query("SELECT * FROM guru as p WHERE p.id_guru='".$data_pembeli['id_guru']."'")->result();
				if(empty($get_pembeli_baseid)){
					$this->response(array('status'=>'fail','message' => 'id kosong'.$data_pembeli['id_guru']));

				}else{

					if (!empty($_FILES["foto"]["name"])) {
						if(move_uploaded_file($_FILES["foto"]["tmp_name"],$uploadfile)){
							$insert_image = "success";
						}
						else{}
					}else{
						$insert_image = "Image Tidak ada Masukan";
					}
				if ($insert_image=="success"){
					//jika photo di update eksekusi query
					$update= $this->db->query("Update guru Set foto ='".$data_pembeli['foto']."' Where id_guru ='".$data_pembeli['id_guru']."'");
					$data_pembeli['foto'] = base_url()."upload/".$user_img;
				}else{
					//jika photo di kosong atau tidak di update eksekusi query
					
					$getPhotoPath =$this->db->query("SELECT foto FROM guru Where id_guru='".$data_pembeli['id_guru']."'")->result();
					if(!empty($getPhotoPath)){
						foreach ($getPhotoPath as $row){
							$user_img = $row->foto;
							$data_pembeli['foto'] = base_url().$user_img;
						}
					}
				}
				if ($update){				
					$this->response(array('status'=>'success','message'=>'berhasil'));
				}
			
		}
	}
	
	function jadwal_post()
	{
		$data['id_guru'] = $this->post('id_guru');
		$data['hari_ngajar'] = $this->post('hari_ngajar');
		$data['jam_ngajar'] = $this->post('jam_ngajar');
			//Validasi input data
		
		$insert = $this->db->insert('jadwal_guru', $data);
		if ($insert) {
			$this->response(array('status' => 'success','message' =>"Berhasil insert"));
			$this->response($data, 200);
		} else {
			$this->response(array('status' => 'fail', 'message' =>"id_guru tidak dalam database"));
			//$this->response(array('status' => 'fail', 502));
		}
	}
		function concatJadwal_get()
	{
		$id_guru = $this->get('id_guru');
		if ($id_guru<> '') {
			$this->db->where('id_guru', $id_guru);
			$this->db->select('concat_ws(" ",hari_ngajar, "pukul", jam_ngajar) as "concatJadwal",jam_ngajar,id_jadwal');
		
			$db = $this->db->get('jadwal_guru')->result();
		} else {
		$this->db->select('concat_ws(" ",hari_ngajar, "pukul", jam_ngajar) as "concatJadwal",jam_ngajar,id_jadwal');
		
			$db = $this->db->get('jadwal_guru')->result();
		}
		$this->response(array("status"=>"success","result" => $db));
	}


}
?>