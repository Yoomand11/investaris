<?php

use Dompdf\Dompdf;

class Barang extends CI_Controller{
	public function __construct(){
		parent::__construct();
		if($this->session->login['role'] != 'petugas' && $this->session->login['role'] != 'admin') redirect();
		$this->data['aktif'] = 'barang';
		$this->load->model('M_barang', 'm_barang');
		$this->load->helper(array('form', 'url'));
	}

	public function index(){
		$this->data['title'] = 'Data Barang';
		$this->data['all_barang'] = $this->m_barang->lihat();
		$this->data['no'] = 1;

		$this->load->view('barang/lihat', $this->data);
	}

	public function tambah(){
		if ($this->session->login['role'] == 'petugas'){
			$this->session->set_flashdata('error', 'Tambah data hanya untuk admin!');
			redirect('dashboard');
		}

		$this->data['title'] = 'Tambah Barang';

		$this->load->view('barang/tambah', $this->data);
	}

	public function proses_tambah(){
		if ($this->session->login['role'] == 'petugas'){
			$this->session->set_flashdata('error', 'Tambah data hanya untuk admin!');
			redirect('dashboard');
		}

		$foto_barang = 'tidak_ada_foto.png';

		if (!empty($_FILES['foto_barang']['name'])) {
			$config['upload_path']   = FCPATH . 'upload/';
			$config['allowed_types'] = 'png|jpg|jpeg|gif';
			$config['max_size']      = 2048;
			$config['file_name']     = 'barang_' . time();

			$this->load->library('upload', $config);

			if ($this->upload->do_upload('foto_barang')) {
				$foto_barang = $this->upload->data('file_name');
			} else {
				$this->session->set_flashdata('error', 'Upload foto gagal: ' . strip_tags($this->upload->display_errors()));
				redirect('barang/tambah');
				return;
			}
		}

		$data = [
			'kode_barang'  => $this->input->post('kode_barang'),
			'nama_barang'  => $this->input->post('nama_barang'),
			'foto_barang'  => $foto_barang,
			'stok'         => $this->input->post('stok'),
			'satuan'       => $this->input->post('satuan'),
		];

		if($this->m_barang->tambah($data)){
			$this->session->set_flashdata('success', 'Data Barang <strong>Berhasil</strong> Ditambahkan!');
			redirect('barang');
		} else {
			$this->session->set_flashdata('error', 'Data Barang <strong>Gagal</strong> Ditambahkan!');
			redirect('barang');
		}
	}

	public function ubah($kode_barang){
		if ($this->session->login['role'] == 'petugas'){
			$this->session->set_flashdata('error', 'Ubah data hanya untuk admin!');
			redirect('dashboard');
		}

		$this->data['title'] = 'Ubah Barang';
		$this->data['barang'] = $this->m_barang->lihat_id($kode_barang);

		$this->load->view('barang/ubah', $this->data);
	}

	public function proses_ubah($kode_barang){
		if ($this->session->login['role'] == 'petugas'){
			$this->session->set_flashdata('error', 'Ubah data hanya untuk admin!');
			redirect('dashboard');
		}

		// Ambil data lama untuk foto
		$barang_lama = $this->m_barang->lihat_id($kode_barang);
		$foto_barang = $barang_lama->foto_barang ?? 'tidak_ada_foto.png';

		if (!empty($_FILES['foto_barang']['name'])) {
			$config['upload_path']   = FCPATH . 'upload/';
			$config['allowed_types'] = 'png|jpg|jpeg|gif';
			$config['max_size']      = 2048;
			$config['file_name']     = 'barang_' . time();

			$this->load->library('upload', $config);

			if ($this->upload->do_upload('foto_barang')) {
				// Hapus foto lama jika bukan default
				if ($foto_barang !== 'tidak_ada_foto.png' && file_exists(FCPATH . 'upload/' . $foto_barang)) {
					@unlink(FCPATH . 'upload/' . $foto_barang);
				}
				$foto_barang = $this->upload->data('file_name');
			} else {
				$this->session->set_flashdata('error', 'Upload foto gagal: ' . strip_tags($this->upload->display_errors()));
				redirect('barang/ubah/' . $kode_barang);
				return;
			}
		}

		$data = [
			'kode_barang'  => $this->input->post('kode_barang'),
			'nama_barang'  => $this->input->post('nama_barang'),
			'foto_barang'  => $foto_barang,
			'stok'         => $this->input->post('stok'),
			'satuan'       => $this->input->post('satuan'),
		];

		if($this->m_barang->ubah($data, $kode_barang)){
			$this->session->set_flashdata('success', 'Data Barang <strong>Berhasil</strong> Diubah!');
			redirect('barang');
		} else {
			$this->session->set_flashdata('error', 'Data Barang <strong>Gagal</strong> Diubah!');
			redirect('barang');
		}
	}

	public function hapus($kode_barang){
		if ($this->session->login['role'] == 'petugas'){
			$this->session->set_flashdata('error', 'Hapus data hanya untuk admin!');
			redirect('dashboard');
		}
		
		if($this->m_barang->hapus($kode_barang)){
			$this->session->set_flashdata('success', 'Data Barang <strong>Berhasil</strong> Dihapus!');
			redirect('barang');
		} else {
			$this->session->set_flashdata('error', 'Data Barang <strong>Gagal</strong> Dihapus!');
			redirect('barang');
		}
	}

	public function export(){
		$dompdf = new Dompdf();
		$this->data['all_barang'] = $this->m_barang->lihat();
		$this->data['title'] = 'Laporan Data Barang';
		$this->data['no'] = 1;

		$dompdf->setPaper('A4', 'Landscape');
		$html = $this->load->view('barang/report', $this->data, true);
		$dompdf->load_html($html);
		$dompdf->render();
		$dompdf->stream('Laporan Data Barang Tanggal ' . date('d F Y'), array("Attachment" => false));
	}
}