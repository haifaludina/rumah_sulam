<?php
include '../koneksi.php';

header('Content-Type: application/json');

if (isset($_GET['id_retur'])) {
    $id_retur = intval($_GET['id_retur']);

    $query = "
        SELECT r.*, p.nama_pelanggan, ps.status AS status_pesanan
        FROM retur r
        JOIN pelanggan p ON r.id_pelanggan = p.id_pelanggan
        JOIN pesanan ps ON r.id_pesanan = ps.id_pesanan
        WHERE r.id_retur = $id_retur
        LIMIT 1
    ";

    $result = mysqli_query($koneksi, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $data = mysqli_fetch_assoc($result);

        // Format dates
        $data['tanggal_pengajuan'] = date('d-m-Y H:i:s', strtotime($data['tanggal_pengajuan']));
        $data['tanggal_retur'] = $data['tanggal_retur'] ? date('d-m-Y', strtotime($data['tanggal_retur'])) : null;
        $data['tanggal_refund'] = $data['tanggal_refund'] ? date('d-m-Y H:i:s', strtotime($data['tanggal_refund'])) : null;

        // Decode refund data if exists
        if (!empty($data['refund_data'])) {
            $data['refund_data'] = json_decode($data['refund_data'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $data['refund_data'] = null;
            }
        }

        // Get items for return
        $itemsQuery = "SELECT * FROM item_pesanan WHERE id_pesanan = " . $data['id_pesanan'];
        $itemsResult = mysqli_query($koneksi, $itemsQuery);

        $items = [];
        if ($itemsResult) {
            while ($item = mysqli_fetch_assoc($itemsResult)) {
                $produkQuery = "SELECT * FROM produk WHERE id_produk = " . $item['id_produk'];
                $produkResult = mysqli_query($koneksi, $produkQuery);
                $produk = mysqli_fetch_assoc($produkResult);
                
                $itemId = 'item_' . $item['id_item_pesanan'];
                $isInRetur = false;
                if (!empty($data['item_retur'])) {
                    $returItems = json_decode($data['item_retur'], true);
                    $isInRetur = is_array($returItems) && in_array($itemId, $returItems);
                }
                
                if ($produk && $isInRetur) {
                    $items[] = [
                        'nama_produk' => $produk['nama_produk'],
                        'harga' => $produk['harga'],
                        'qty' => $item['jumlah'],
                        'subtotal' => $produk['harga'] * $item['jumlah'],
                        'kondisi' => $item['kondisi'] ?? 'Baik'
                    ];
                }
            }
        }

        // Get custom items for return
        $customItemsQuery = "SELECT * FROM detail_pesanan_kustom WHERE id_pesanan = " . $data['id_pesanan'];
        $customItemsResult = mysqli_query($koneksi, $customItemsQuery);

        if ($customItemsResult) {
            while ($customItem = mysqli_fetch_assoc($customItemsResult)) {
                $itemId = 'item_' . $customItem['id_detail_pesanan'];
                $isInRetur = false;
                if (!empty($data['item_retur'])) {
                    $returItems = json_decode($data['item_retur'], true);
                    $isInRetur = is_array($returItems) && in_array($itemId, $returItems);
                }
                
                if ($isInRetur) {
                    $items[] = [
                        'nama_produk' => 'Custom: ' . $customItem['nama_produk'],
                        'harga' => $customItem['harga'],
                        'qty' => 1,
                        'subtotal' => $customItem['harga'],
                        'kondisi' => $customItem['kondisi'] ?? 'Baik',
                        'custom_details' => [
                            'warna_kain' => $customItem['warna_kain'],
                            'warna_benang' => $customItem['warna_benang'],
                            'motif' => $customItem['motif'],
                            'catatan' => $customItem['catatan']
                        ]
                    ];
                }
            }
        }

        $data['items'] = $items;

        echo json_encode([
            'success' => true,
            'data' => $data
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Data retur tidak ditemukan.'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Parameter id_retur tidak ditemukan.'
    ]);
}