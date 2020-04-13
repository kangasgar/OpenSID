<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Mailbox_model extends CI_Model {

	/**
	 * Gunakan model ini untuk memindahkan semua method terkait mailbox layanan mandiri.
	 * Dimana layanan mailbox memiliki perlakuan yang sepenuhnya berbeda dengan komentar web
	 */
	public function __construct()
	{
		parent::__construct();
		$this->load->model('referensi_model');
		
	}

	public function autocomplete()
	{
		$str = autocomplete_str('isi_pesan', 'kotak_pesan');
		return $str;
	}

	public function list_data($o=0, $offset=0, $limit=500, $kat=0)
	{
		$this->db->select('k.*, p.nik, p.nama');
		$this->list_data_sql($kat);
		
		switch ($o)
		{
			case 1: $this->db->order_by('p.nama', DESC); break;
			case 2: $this->db->order_by('p.nama', ASC);; break;
			case 3: $this->db->order_by('p.nik', DESC);; break;
			case 4: $this->db->order_by('p.nik', ASC); break;
			case 5: $this->db->order_by('k.baca', DESC); break;
			case 6: $this->db->order_by('k.baca', ASC); break;
			case 7: $this->db->order_by('created_at', DESC); break;
			case 8: $this->db->order_by('created_at', ASC); break;

			default: $this->db->order_by('created_at', DESC); break;
		}

		$data = $this->db->limit($limit, $offset)->get()->result_array();
		$j = $offset;
		for ($i=0; $i<count($data); $i++)
		{
			$data[$i]['no'] = $j + 1;
			$j++;
		}
		return $data;
	}

	private function list_data_sql($kat=1)
	{
		$this->db->from('kotak_pesan k');

		if ($kat == 1) {
			$this->db->join('tweb_penduduk p','p.id = k.id_pengirim', 'left')->where('tipe', 1); //pengirim
		}else{			
			$this->db->join('tweb_penduduk p','p.id = k.id_penerima', 'left')->where('tipe', 2); //penerima
		}
		$this->filter_nik_sql();
		$this->search_sql();
		$this->filter_sql();
	}

	private function filter_nik_sql()
	{
		if (isset($_SESSION['filter_nik']))
		{
			$kf = $_SESSION['filter_nik'];
			$this->db->where('p.nik', $kf);
		}
	}

	private function search_sql()
	{
		if (isset($_SESSION['cari']))
		{
			$cari = $_SESSION['cari'];
			$kw = $this->db->escape_like_str($cari);
			$this->db->like('isi_pesan', $kw)->or_like('subjek', $kw);
		}
	}

	private function filter_sql()
	{		
		if (isset($_SESSION['filter']))
		{
			$kf = $_SESSION['filter'];
			if ($kf == 3) {
				$this->db->where('k.status', 1);
			}
			else 
			{
				$this->db->where('k.baca', $kf);
			}
		}
		else
		{
			$this->db->where('k.status !=', 1);
		}
	}

	public function paging($p=1, $o=0, $kat=0)
	{
		$this->db->select('COUNT(*) AS jml');
		$this->list_data_sql($kat);
		$row = $this->db->get()->row_array();

		$this->load->library('paging');
		$cfg['page'] = $p;
		$cfg['per_page'] = $_SESSION['per_page'];
		$cfg['num_rows'] = $jml;
		$this->paging->init($cfg);

		return $this->paging;
	}


	public function baca($id, $baca)
	{
		$outp = $this->db->where('id', $id)
			->update('kotak_pesan', array('baca' => $baca));
		
		status_sukses($outp); //Tampilkan Pesan
	}

	public function archive($id='', $semua=false)
	{
		if (!$semua) $this->session->success = 1;
		
		$archive = array(
			'status' => 1,
			'updated_at' => date('Y-m-d H:i:s')
		);
		$outp = $this->db->where('id', $id)->update('kotak_pesan', $archive);

		status_sukses($outp, $gagal_saja=true); //Tampilkan Pesan
	}

	public function archive_all()
	{
		$this->session->success = 1;

		$id_cb = $_POST['id_cb'];
		foreach ($id_cb as $id)
		{
			$this->archive($id, $semua=true);
		}
	}

	

	public function insert($data)
	{
		$data = $data;
		$data['id'] = 775;
		$data['created_at'] = date('Y-m-d H:i:s');
		$data['updated_at'] = date('Y-m-d H:i:s');
		$outp = $this->db->insert('komentar', $data);
		if ($outp) $_SESSION['success'] = 1;
		else $_SESSION['success'] = -1;
	}

	public function list_menu()
	{
		return $this->referensi_model->list_kode_array(KATEGORI_MAILBOX);
	}

	public function get_kat_nama($kat)
	{
		$sub_menu = $this->list_menu();	
		$data = $sub_menu[$kat];
		return $data;
	}

	/**
	 * Tipe 1: Inbox untuk admin, Outbox untuk pengguna layanan mandiri
	 * Tipe 2: Outbox untuk admin, Inbox untuk pengguna layanan mandiri
	 */

	public function get_inbox_user($nik)
	{
		$outp = $this->db
			->where('nik', $nik)
			->where('tipe', 2)
			->where('id', 775)
			->from('komentar')
			->order_by('id', 'DESC')
			->get()
			->result_array();
		$j = 1;
		for ($i=0; $i < count($outp); $i++) 
		{ 
			$outp[$i]['no'] = $j++;
		}
		return $outp;
	}

	public function get_outbox_user($nik)
	{
		$outp = $this->db
			->where('nik', $nik)
			->where('tipe', 1)
			->where('id', 775)
			->from('komentar')
			->order_by('id','DESC')
			->get()
			->result_array();
		$j = 1;
		for ($i=0; $i < count($outp); $i++) 
		{ 
			$outp[$i]['no'] = $j++;
		}
		return $outp;
	}

	public function get_pesan($nik, $id)
	{
		return $this->db
			->where('nik', $nik)
			->where('id', $id)
			->where('id', 775)
			->from('komentar')
			->get()
			->row_array();
	}

	public function ubah_status_pesan($nik, $id, $status)
	{
		return $this->db
			->where('nik', $nik)
			->where('id', $id)
			->where('tipe', 2)
			->where('id', 775)
			->update('komentar', array('status' => $status));
	}

	// Perbaikan afa28
	public function get_mailbox($id=0)
	{
		$data = $this->db->where('id', $id)
			->get('mailbox')
			->row_array();

		return $data;
	}
}
?>