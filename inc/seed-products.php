<?php
/**
 * PetShop - Seed Sample Products
 * Tạo sản phẩm mẫu cho các danh mục
 * 
 * @package PetShop
 */

// Ngăn truy cập trực tiếp
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Dữ liệu sản phẩm mẫu
 */
function petshop_get_sample_products() {
    $categories = array(
        'giay-dep', 'non', 'phu-kien-them', 'quan-ao', 'vong-co',
        'ca', 'chim', 'cho', 'hamster', 'meo', 'tho',
        'banh-thuong', 'thuc-an-dieu-tri-benh', 'thuc-an-hat', 'thuc-an-huu-co', 'thuc-an-uot'
    );
    $sample_images = array(
        'https://images.unsplash.com/photo-1587300003388-59208cc962cb?w=600',
        'https://images.unsplash.com/photo-1544568100-847a948585b9?w=600',
        'https://images.unsplash.com/photo-1514888286974-6c03e2ca1dba?w=600',
        'https://images.unsplash.com/photo-1520302630591-fd1c66edc19d?w=600',
        'https://images.unsplash.com/photo-1522069169874-c58ec4b76be5?w=600',
        'https://images.unsplash.com/photo-1574158622682-e40e69881006?w=600',
        'https://images.unsplash.com/photo-1583511655857-d19b40a7a54e?w=600',
        'https://images.unsplash.com/photo-1552728089-57bdde30beb3?w=600',
        'https://images.unsplash.com/photo-1589924691995-400dc9ecc119?w=600',
        'https://images.unsplash.com/photo-1518717758536-85ae4e2f2d2c?w=600',
    );
    $products = array();
    foreach ($categories as $cat) {
        $cat_products = array();
        for ($i = 1; $i <= 20; $i++) {
            $cat_products[] = array(
                'title' => ucfirst($cat) . ' mẫu ' . $i,
                'price' => rand(50000, 500000),
                'sale_price' => rand(0, 400000),
                'sku' => strtoupper($cat) . '-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'stock' => rand(5, 100),
                'image' => $sample_images[array_rand($sample_images)],
                'description' => 'Sản phẩm mẫu ' . $i . ' thuộc danh mục ' . $cat . '. Chất lượng tốt, giá hợp lý.',
                'content' => '<h3>Thông tin chi tiết</h3><ul><li>Mẫu số ' . $i . '</li><li>Danh mục: ' . $cat . '</li><li>Đa dạng màu sắc, kích thước</li></ul>'
            );
        }
        $products[$cat] = $cat_products;
    }
    return $products;
}

/**
 * Dữ liệu sản phẩm đặc thù theo danh mục
 */
function petshop_get_specific_products() {
    return array(
        // Vòng cổ
        'vong-co' => array(
            array(
                'title' => 'Vòng cổ LED phát sáng',
                'price' => 125000,
                'sale_price' => 99000,
                'sku' => 'VC-001',
                'stock' => 90,
                'image' => 'https://images.unsplash.com/photo-1587300003388-59208cc962cb?w=600',
                'description' => 'Vòng cổ LED phát sáng nhiều chế độ, giúp nhìn thấy thú cưng trong đêm tối.',
                'content' => '<h3>Tính năng</h3>
<ul>
<li>3 chế độ sáng: Sáng liên tục, nhấp nháy nhanh, nhấp nháy chậm</li>
<li>Pin sạc USB tiện lợi</li>
<li>Chống nước IPX4</li>
<li>Có nhiều màu: Đỏ, Xanh lá, Xanh dương, Hồng</li>
</ul>'
            ),
            array(
                'title' => 'Vòng cổ da cao cấp có khắc tên',
                'price' => 220000,
                'sale_price' => 0,
                'sku' => 'VC-002',
                'stock' => 30,
                'image' => 'https://images.unsplash.com/photo-1544568100-847a948585b9?w=600',
                'description' => 'Vòng cổ da bò thật 100%, có thể khắc tên và số điện thoại theo yêu cầu.',
                'content' => '<h3>Đặc điểm cao cấp</h3>
<ul>
<li>Da bò thật 100%</li>
<li>Khóa kim loại không gỉ</li>
<li>Khắc laser tên + SĐT miễn phí</li>
<li>Bảo hành 1 năm</li>
</ul>'
            ),
            array(
                'title' => 'Vòng cổ chống ve rận 8 tháng',
                'price' => 350000,
                'sale_price' => 299000,
                'sku' => 'VC-003',
                'stock' => 65,
                'image' => 'https://images.unsplash.com/photo-1583511655857-d19b40a7a54e?w=600',
                'description' => 'Vòng cổ diệt và phòng ngừa ve, bọ chét hiệu quả trong 8 tháng, an toàn cho thú cưng.',
                'content' => '<h3>Công dụng</h3>
<ul>
<li>Diệt ve, bọ chét, rận</li>
<li>Hiệu quả kéo dài 8 tháng</li>
<li>Không mùi, không gây kích ứng</li>
<li>Chống nước</li>
<li>Phù hợp chó mèo từ 8 tuần tuổi</li>
</ul>'
            ),
        ),
        
        // ========================================
        // DANH MỤC: THÚ CƯNG
        // ========================================
        
        // Cá
        'ca' => array(
            array(
                'title' => 'Cá Betta Halfmoon đuôi trăng',
                'price' => 150000,
                'sale_price' => 0,
                'sku' => 'CA-001',
                'stock' => 20,
                'image' => 'https://images.unsplash.com/photo-1520302630591-fd1c66edc19d?w=600',
                'description' => 'Cá Betta Halfmoon với đuôi hình bán nguyệt tuyệt đẹp, màu sắc rực rỡ, khỏe mạnh.',
                'content' => '<h3>Thông tin</h3>
<ul>
<li>Xuất xứ: Thái Lan</li>
<li>Kích thước: 4-5cm</li>
<li>Tuổi thọ: 2-3 năm</li>
<li>Nhiệt độ nước: 24-28°C</li>
</ul>
<h3>Lưu ý</h3>
<p>Nuôi riêng từng con, không nuôi chung 2 cá đực.</p>'
            ),
            array(
                'title' => 'Cá vàng Oranda đầu lân',
                'price' => 250000,
                'sale_price' => 199000,
                'sku' => 'CA-002',
                'stock' => 15,
                'image' => 'https://images.unsplash.com/photo-1522069169874-c58ec4b76be5?w=600',
                'description' => 'Cá vàng Oranda với phần đầu lân đặc trưng, màu cam đỏ rực rỡ, size 8-10cm.',
                'content' => '<h3>Đặc điểm</h3>
<ul>
<li>Đầu lân phát triển đẹp</li>
<li>Màu cam đỏ bóng mượt</li>
<li>Bơi chậm, hiền lành</li>
<li>Cần bể tối thiểu 50L</li>
</ul>'
            ),
        ),
        
        // Chim
        'chim' => array(
            array(
                'title' => 'Chim Yến Phụng cặp',
                'price' => 350000,
                'sale_price' => 0,
                'sku' => 'CHIM-001',
                'stock' => 10,
                'image' => 'https://images.unsplash.com/photo-1552728089-57bdde30beb3?w=600',
                'description' => 'Cặp chim Yến Phụng (Vẹt Úc) nhiều màu sắc, đã thuần, biết ăn tay.',
                'content' => '<h3>Thông tin</h3>
<ul>
<li>Tuổi: 3-4 tháng</li>
<li>Đã thuần, thân thiện</li>
<li>Ăn hạt kê, rau xanh</li>
<li>Tuổi thọ: 5-8 năm</li>
</ul>
<h3>Bao gồm</h3>
<p>1 cặp chim + thức ăn 1 tuần + tư vấn chăm sóc</p>'
            ),
            array(
                'title' => 'Vẹt Cockatiel Lutino vàng',
                'price' => 1200000,
                'sale_price' => 999000,
                'sku' => 'CHIM-002',
                'stock' => 5,
                'image' => 'https://images.unsplash.com/photo-1544923246-77307dd628b5?w=600',
                'description' => 'Vẹt Cockatiel Lutino lông vàng, má đỏ cam, đã biết huýt sáo, cực kỳ thông minh.',
                'content' => '<h3>Đặc điểm</h3>
<ul>
<li>Lông vàng kem tuyệt đẹp</li>
<li>Má đỏ cam đặc trưng</li>
<li>Đã thuần, đậu tay</li>
<li>Biết huýt sáo vài bài</li>
<li>Tuổi thọ: 15-20 năm</li>
</ul>'
            ),
            array(
                'title' => 'Chim Finch Zebra cặp',
                'price' => 180000,
                'sale_price' => 150000,
                'sku' => 'CHIM-003',
                'stock' => 18,
                'image' => 'https://images.unsplash.com/photo-1480044965905-02098d419e96?w=600',
                'description' => 'Cặp chim Finch Zebra nhỏ xinh, hót hay, dễ nuôi, sinh sản tốt.',
                'content' => '<h3>Thông tin</h3>
<ul>
<li>Size nhỏ gọn: 10-11cm</li>
<li>Hót líu lo vui tai</li>
<li>Dễ nuôi, ít bệnh</li>
<li>Sinh sản quanh năm</li>
</ul>'
            ),
        ),
        
        // Chó
        'cho' => array(
            array(
                'title' => 'Chó Poodle Tiny nâu đỏ 2 tháng',
                'price' => 8500000,
                'sale_price' => 0,
                'sku' => 'CHO-001',
                'stock' => 3,
                'image' => 'https://images.unsplash.com/photo-1587300003388-59208cc962cb?w=600',
                'description' => 'Chó Poodle Tiny lông nâu đỏ, 2 tháng tuổi, đã tiêm 2 mũi, có giấy VKA.',
                'content' => '<h3>Thông tin chi tiết</h3>
<ul>
<li>Giống: Poodle Tiny</li>
<li>Màu: Nâu đỏ (Apricot)</li>
<li>Tuổi: 2 tháng</li>
<li>Cân nặng hiện tại: 800g</li>
<li>Dự kiến trưởng thành: 2-2.5kg</li>
</ul>
<h3>Đã tiêm phòng</h3>
<ul>
<li>Mũi 1: Parvo + Care</li>
<li>Mũi 2: 5 bệnh tổng hợp</li>
<li>Đã tẩy giun</li>
</ul>
<h3>Chế độ bảo hành</h3>
<p>Bảo hành sức khỏe 15 ngày, hỗ trợ tư vấn trọn đời.</p>'
            ),
            array(
                'title' => 'Chó Corgi Pembroke 3 tháng',
                'price' => 12000000,
                'sale_price' => 10500000,
                'sku' => 'CHO-002',
                'stock' => 2,
                'image' => 'https://images.unsplash.com/photo-1544568100-847a948585b9?w=600',
                'description' => 'Chó Corgi Pembroke chân ngắn đáng yêu, 3 tháng tuổi, lông vàng trắng, có VKA.',
                'content' => '<h3>Thông tin</h3>
<ul>
<li>Giống: Corgi Pembroke</li>
<li>Màu: Vàng trắng</li>
<li>Tuổi: 3 tháng</li>
<li>Giấy tờ: VKA đầy đủ</li>
</ul>
<h3>Đặc điểm</h3>
<ul>
<li>Tính cách vui vẻ, năng động</li>
<li>Thông minh, dễ huấn luyện</li>
<li>Thân thiện với trẻ em</li>
</ul>'
            ),
            array(
                'title' => 'Chó Shiba Inu vàng 2.5 tháng',
                'price' => 15000000,
                'sale_price' => 0,
                'sku' => 'CHO-003',
                'stock' => 2,
                'image' => 'https://images.unsplash.com/photo-1583511655857-d19b40a7a54e?w=600',
                'description' => 'Chó Shiba Inu Nhật Bản thuần chủng, lông vàng cam, mặt cáo đặc trưng.',
                'content' => '<h3>Nguồn gốc</h3>
<p>Bố mẹ nhập khẩu Nhật Bản, có giấy tờ FCI đầy đủ.</p>
<h3>Thông tin</h3>
<ul>
<li>Màu: Vàng cam (Red Sesame)</li>
<li>Tuổi: 2.5 tháng</li>
<li>Đã tiêm 2 mũi vaccine</li>
<li>Có microchip</li>
</ul>'
            ),
        ),
        
        // Hamster
        'hamster' => array(
            array(
                'title' => 'Hamster Winter White trắng',
                'price' => 80000,
                'sale_price' => 65000,
                'sku' => 'HAM-001',
                'stock' => 30,
                'image' => 'https://images.unsplash.com/photo-1425082661705-1834bfd09dca?w=600',
                'description' => 'Hamster Winter White lông trắng tuyết, hiền lành, dễ nuôi, không cắn.',
                'content' => '<h3>Thông tin</h3>
<ul>
<li>Giống: Winter White</li>
<li>Màu: Trắng tuyết</li>
<li>Tuổi: 1-1.5 tháng</li>
<li>Tuổi thọ: 1.5-2 năm</li>
</ul>
<h3>Đặc điểm</h3>
<ul>
<li>Hiền lành, ít cắn</li>
<li>Hoạt động về đêm</li>
<li>Dễ chăm sóc</li>
</ul>'
            ),
            array(
                'title' => 'Hamster Robo lùn siêu nhỏ',
                'price' => 120000,
                'sale_price' => 0,
                'sku' => 'HAM-002',
                'stock' => 20,
                'image' => 'https://images.unsplash.com/photo-1425082661705-1834bfd09dca?w=600',
                'description' => 'Hamster Roborovski siêu nhỏ siêu nhanh, lông nâu xám, má trắng đáng yêu.',
                'content' => '<h3>Đặc điểm Robo</h3>
<ul>
<li>Kích thước nhỏ nhất: 4-5cm</li>
<li>Cực kỳ nhanh nhẹn</li>
<li>Lông nâu xám mượt</li>
<li>Tuổi thọ: 3-3.5 năm (lâu nhất)</li>
</ul>
<p><strong>Lưu ý:</strong> Khó bắt hơn các loại khác do rất nhanh.</p>'
            ),
            array(
                'title' => 'Hamster Syrian Golden lông dài',
                'price' => 150000,
                'sale_price' => 129000,
                'sku' => 'HAM-003',
                'stock' => 15,
                'image' => 'https://images.unsplash.com/photo-1425082661705-1834bfd09dca?w=600',
                'description' => 'Hamster Syrian lông dài màu Golden, size lớn, hiền lành, thích vuốt ve.',
                'content' => '<h3>Hamster Syrian</h3>
<ul>
<li>Size lớn nhất: 15-17cm</li>
<li>Lông dài mềm mượt</li>
<li>Rất hiền, thích được ôm</li>
<li>PHẢI NUÔI RIÊNG (không nuôi chung)</li>
</ul>'
            ),
        ),
        
        // Mèo
        'meo' => array(
            array(
                'title' => 'Mèo Anh lông ngắn xám xanh 3 tháng',
                'price' => 6500000,
                'sale_price' => 5900000,
                'sku' => 'MEO-001',
                'stock' => 4,
                'image' => 'https://images.unsplash.com/photo-1514888286974-6c03e2ca1dba?w=600',
                'description' => 'Mèo Anh lông ngắn (British Shorthair) màu xám xanh chuẩn, mặt tròn, má phính.',
                'content' => '<h3>Thông tin</h3>
<ul>
<li>Giống: British Shorthair</li>
<li>Màu: Blue (Xám xanh)</li>
<li>Tuổi: 3 tháng</li>
<li>Đã tiêm: 2 mũi vaccine</li>
<li>Đã tẩy giun, ve</li>
</ul>
<h3>Tính cách</h3>
<p>Hiền lành, điềm đạm, thích nằm một chỗ. Rất phù hợp nuôi trong chung cư.</p>'
            ),
            array(
                'title' => 'Mèo Scottish Fold tai cụp 2 tháng',
                'price' => 9000000,
                'sale_price' => 0,
                'sku' => 'MEO-002',
                'stock' => 2,
                'image' => 'https://images.unsplash.com/photo-1574158622682-e40e69881006?w=600',
                'description' => 'Mèo Scottish Fold tai cụp đáng yêu, lông tabby silver, mắt tròn to.',
                'content' => '<h3>Đặc điểm Scottish Fold</h3>
<ul>
<li>Tai cụp xuống đặc trưng</li>
<li>Màu: Silver Tabby</li>
<li>Mắt to tròn màu đồng</li>
<li>Tính cách: Hiền, thích chơi</li>
</ul>
<h3>Lưu ý</h3>
<p>Không lai với Scottish Fold khác để tránh bệnh xương.</p>'
            ),
            array(
                'title' => 'Mèo Munchkin chân ngắn vàng kem',
                'price' => 12000000,
                'sale_price' => 10000000,
                'sku' => 'MEO-003',
                'stock' => 2,
                'image' => 'https://images.unsplash.com/photo-1495360010541-f48722b34f7d?w=600',
                'description' => 'Mèo Munchkin chân ngắn siêu dễ thương, lông vàng kem, tính cách vui vẻ.',
                'content' => '<h3>Mèo Munchkin</h3>
<ul>
<li>Đặc điểm: Chân ngắn đáng yêu</li>
<li>Màu: Vàng kem (Cream)</li>
<li>Rất năng động dù chân ngắn</li>
<li>Thân thiện với người và thú cưng khác</li>
</ul>'
            ),
        ),
        
        // Thỏ
        'tho' => array(
            array(
                'title' => 'Thỏ Hà Lan lùn đen trắng',
                'price' => 250000,
                'sale_price' => 199000,
                'sku' => 'THO-001',
                'stock' => 12,
                'image' => 'https://images.unsplash.com/photo-1585110396000-c9ffd4e4b308?w=600',
                'description' => 'Thỏ Hà Lan (Holland Lop) tai cụp, màu đen trắng, size mini cực kỳ đáng yêu.',
                'content' => '<h3>Thông tin</h3>
<ul>
<li>Giống: Holland Lop</li>
<li>Màu: Đen trắng</li>
<li>Tuổi: 1.5 tháng</li>
<li>Size trưởng thành: 1.5-2kg</li>
</ul>
<h3>Đặc điểm</h3>
<ul>
<li>Tai cụp xuống 2 bên</li>
<li>Hiền lành, thích vuốt ve</li>
<li>Ăn cỏ, rau, viên nén</li>
</ul>'
            ),
            array(
                'title' => 'Thỏ Angora lông xù trắng',
                'price' => 450000,
                'sale_price' => 0,
                'sku' => 'THO-002',
                'stock' => 8,
                'image' => 'https://images.unsplash.com/photo-1585110396000-c9ffd4e4b308?w=600',
                'description' => 'Thỏ Angora lông dài xù bông như đám mây, màu trắng tinh khôi.',
                'content' => '<h3>Thỏ Angora</h3>
<ul>
<li>Lông dài, xù, mềm như bông</li>
<li>Cần chải lông thường xuyên</li>
<li>Size: 2-3kg khi trưởng thành</li>
<li>Tuổi thọ: 5-8 năm</li>
</ul>
<h3>Chăm sóc</h3>
<p>Cần chải lông 2-3 lần/tuần để tránh rối lông.</p>'
            ),
        ),
        
        // ========================================
        // DANH MỤC: THỰC PHẨM
        // ========================================
        
        // Bánh thưởng
        'banh-thuong' => array(
            array(
                'title' => 'Bánh thưởng Pedigree Dentastix',
                'price' => 85000,
                'sale_price' => 69000,
                'sku' => 'BT-001',
                'stock' => 100,
                'image' => 'https://images.unsplash.com/photo-1589924691995-400dc9ecc119?w=600',
                'description' => 'Bánh thưởng Pedigree Dentastix giúp làm sạch răng, giảm mảng bám, thơm miệng.',
                'content' => '<h3>Công dụng</h3>
<ul>
<li>Giảm 80% mảng bám răng</li>
<li>Làm sạch răng hiệu quả</li>
<li>Thơm miệng</li>
<li>Bổ sung canxi</li>
</ul>
<h3>Hướng dẫn</h3>
<p>Cho 1 thanh/ngày với chó 10-25kg.</p>'
            ),
            array(
                'title' => 'Snack thịt gà sấy khô cho mèo',
                'price' => 55000,
                'sale_price' => 0,
                'sku' => 'BT-002',
                'stock' => 150,
                'image' => 'https://images.unsplash.com/photo-1589924691995-400dc9ecc119?w=600',
                'description' => 'Thịt gà sấy khô 100% tự nhiên, không phụ gia, là phần thưởng hoàn hảo cho mèo.',
                'content' => '<h3>Thành phần</h3>
<ul>
<li>100% ức gà tươi</li>
<li>Không chất bảo quản</li>
<li>Không phẩm màu</li>
<li>Không muối</li>
</ul>
<h3>Lợi ích</h3>
<ul>
<li>Protein cao</li>
<li>Ít chất béo</li>
<li>Giàu taurine tự nhiên</li>
</ul>'
            ),
            array(
                'title' => 'Xương gặm sạch răng cho chó',
                'price' => 45000,
                'sale_price' => 35000,
                'sku' => 'BT-003',
                'stock' => 200,
                'image' => 'https://images.unsplash.com/photo-1589924691995-400dc9ecc119?w=600',
                'description' => 'Xương gặm làm từ da bò, giúp chó giải stress và sạch răng tự nhiên.',
                'content' => '<h3>Đặc điểm</h3>
<ul>
<li>Làm từ da bò 100%</li>
<li>Sấy khô tự nhiên</li>
<li>Không hóa chất</li>
<li>Kích thước: 15cm</li>
</ul>'
            ),
        ),
        
        // Thức ăn điều trị bệnh
        'thuc-an-dieu-tri-benh' => array(
            array(
                'title' => 'Royal Canin Urinary cho mèo tiết niệu',
                'price' => 520000,
                'sale_price' => 469000,
                'sku' => 'TAB-001',
                'stock' => 40,
                'image' => 'https://images.unsplash.com/photo-1589924691995-400dc9ecc119?w=600',
                'description' => 'Thức ăn hỗ trợ điều trị sỏi thận, nhiễm trùng đường tiết niệu cho mèo.',
                'content' => '<h3>Công dụng</h3>
<ul>
<li>Hỗ trợ hòa tan sỏi struvite</li>
<li>Ngăn ngừa tái phát sỏi</li>
<li>pH nước tiểu cân bằng</li>
<li>Tăng lượng nước tiểu</li>
</ul>
<h3>Khuyến cáo</h3>
<p>Sử dụng theo chỉ định của bác sĩ thú y.</p>'
            ),
            array(
                'title' => 'Hills Digestive Care cho chó tiêu hóa',
                'price' => 650000,
                'sale_price' => 0,
                'sku' => 'TAB-002',
                'stock' => 30,
                'image' => 'https://images.unsplash.com/photo-1589924691995-400dc9ecc119?w=600',
                'description' => 'Thức ăn hỗ trợ hệ tiêu hóa cho chó bị đau bụng, tiêu chảy, táo bón.',
                'content' => '<h3>Đặc điểm</h3>
<ul>
<li>Chất xơ prebiotic</li>
<li>Dễ tiêu hóa</li>
<li>Cải thiện phân</li>
<li>Tăng cường hệ miễn dịch đường ruột</li>
</ul>
<h3>Quy cách</h3>
<p>Bao 2kg - Dùng trong 2-4 tuần hoặc theo chỉ định.</p>'
            ),
        ),
        
        // Thức ăn hạt
        'thuc-an-hat' => array(
            array(
                'title' => 'Royal Canin Medium Adult 10kg',
                'price' => 1250000,
                'sale_price' => 1099000,
                'sku' => 'TAH-001',
                'stock' => 50,
                'image' => 'https://images.unsplash.com/photo-1589924691995-400dc9ecc119?w=600',
                'description' => 'Thức ăn hạt Royal Canin cho chó trưởng thành size trung 11-25kg, bao 10kg.',
                'content' => '<h3>Đặc điểm</h3>
<ul>
<li>Cho chó 11-25kg, 1-7 tuổi</li>
<li>Hỗ trợ tiêu hóa khỏe mạnh</li>
<li>Da và lông bóng mượt</li>
<li>Duy trì cân nặng lý tưởng</li>
</ul>
<h3>Thành phần</h3>
<p>Thịt gà, gạo, ngô, dầu cá, rau củ...</p>'
            ),
            array(
                'title' => 'Whiskas Adult vị cá biển 7kg',
                'price' => 450000,
                'sale_price' => 0,
                'sku' => 'TAH-002',
                'stock' => 80,
                'image' => 'https://images.unsplash.com/photo-1589924691995-400dc9ecc119?w=600',
                'description' => 'Thức ăn hạt Whiskas cho mèo trưởng thành, vị cá biển thơm ngon.',
                'content' => '<h3>Đặc điểm</h3>
<ul>
<li>Vị cá biển hấp dẫn</li>
<li>Giàu Omega 3 & 6</li>
<li>Taurine bảo vệ tim & mắt</li>
<li>Canxi cho xương chắc khỏe</li>
</ul>
<h3>Quy cách</h3>
<p>Bao 7kg - Tiết kiệm hơn 20% so với gói nhỏ.</p>'
            ),
            array(
                'title' => 'Pedigree Puppy vị gà & trứng 3kg',
                'price' => 295000,
                'sale_price' => 249000,
                'sku' => 'TAH-003',
                'stock' => 60,
                'image' => 'https://images.unsplash.com/photo-1589924691995-400dc9ecc119?w=600',
                'description' => 'Thức ăn hạt Pedigree cho chó con 2-12 tháng, vị gà và trứng bổ dưỡng.',
                'content' => '<h3>Dành cho chó con</h3>
<ul>
<li>Tuổi: 2-12 tháng</li>
<li>DHA phát triển não bộ</li>
<li>Canxi cho xương & răng</li>
<li>Hạt nhỏ dễ nhai</li>
</ul>'
            ),
            array(
                'title' => 'Smartheart Gold Puppy 3kg',
                'price' => 320000,
                'sale_price' => 0,
                'sku' => 'TAH-004',
                'stock' => 45,
                'image' => 'https://images.unsplash.com/photo-1589924691995-400dc9ecc119?w=600',
                'description' => 'Thức ăn cao cấp Smartheart Gold cho chó con, công thức vàng dinh dưỡng.',
                'content' => '<h3>Smartheart Gold</h3>
<ul>
<li>Công thức cao cấp</li>
<li>Protein từ thịt gà thật</li>
<li>Omega 3&6 từ dầu cá</li>
<li>Không phẩm màu nhân tạo</li>
</ul>'
            ),
        ),
        
        // Thức ăn hữu cơ
        'thuc-an-huu-co' => array(
            array(
                'title' => 'Organic Paws thức ăn hữu cơ cho chó',
                'price' => 890000,
                'sale_price' => 799000,
                'sku' => 'TAHO-001',
                'stock' => 25,
                'image' => 'https://images.unsplash.com/photo-1589924691995-400dc9ecc119?w=600',
                'description' => 'Thức ăn hữu cơ 100% từ nguyên liệu organic, không GMO, không hóa chất.',
                'content' => '<h3>100% Organic</h3>
<ul>
<li>Nguyên liệu hữu cơ đạt chuẩn USDA</li>
<li>Không biến đổi gen (Non-GMO)</li>
<li>Không thuốc trừ sâu</li>
<li>Không hormone tăng trưởng</li>
</ul>
<h3>Thành phần</h3>
<p>Gà hữu cơ, gạo lứt, rau củ hữu cơ, dầu dừa...</p>'
            ),
            array(
                'title' => 'Natural Balance Organic cho mèo',
                'price' => 750000,
                'sale_price' => 0,
                'sku' => 'TAHO-002',
                'stock' => 20,
                'image' => 'https://images.unsplash.com/photo-1589924691995-400dc9ecc119?w=600',
                'description' => 'Thức ăn hữu cơ Natural Balance cho mèo, công thức cân bằng dinh dưỡng.',
                'content' => '<h3>Natural Balance</h3>
<ul>
<li>Protein từ cá hồi hữu cơ</li>
<li>Grain-free (không ngũ cốc)</li>
<li>Taurine tự nhiên</li>
<li>Không chất tạo màu, mùi</li>
</ul>'
            ),
        ),
        
        // Thức ăn ướt
        'thuc-an-uot' => array(
            array(
                'title' => 'Pate Whiskas vị cá ngừ 85g x 12',
                'price' => 180000,
                'sale_price' => 155000,
                'sku' => 'TAU-001',
                'stock' => 100,
                'image' => 'https://images.unsplash.com/photo-1589924691995-400dc9ecc119?w=600',
                'description' => 'Pate Whiskas cho mèo vị cá ngừ thơm ngon, hộp 12 gói 85g tiện lợi.',
                'content' => '<h3>Whiskas Pate</h3>
<ul>
<li>Vị cá ngừ hấp dẫn</li>
<li>Độ ẩm cao, tốt cho thận</li>
<li>Bổ sung taurine</li>
<li>Gói 85g tiện lợi</li>
</ul>
<h3>Quy cách</h3>
<p>Hộp 12 gói - Tiết kiệm 15%</p>'
            ),
            array(
                'title' => 'Pedigree Pate vị bò & gan 130g x 6',
                'price' => 120000,
                'sale_price' => 0,
                'sku' => 'TAU-002',
                'stock' => 80,
                'image' => 'https://images.unsplash.com/photo-1589924691995-400dc9ecc119?w=600',
                'description' => 'Pate Pedigree cho chó vị bò và gan, giàu protein và vitamin.',
                'content' => '<h3>Pedigree Pate</h3>
<ul>
<li>Thịt bò và gan thật</li>
<li>Giàu protein</li>
<li>Vitamin và khoáng chất</li>
<li>Không chất bảo quản</li>
</ul>'
            ),
            array(
                'title' => 'Royal Canin Recovery cho thú yếu',
                'price' => 45000,
                'sale_price' => 39000,
                'sku' => 'TAU-003',
                'stock' => 60,
                'image' => 'https://images.unsplash.com/photo-1589924691995-400dc9ecc119?w=600',
                'description' => 'Pate dinh dưỡng cao cho chó mèo hồi phục sau ốm, phẫu thuật, suy nhược.',
                'content' => '<h3>Recovery</h3>
<ul>
<li>Năng lượng cao</li>
<li>Dễ tiêu hóa</li>
<li>Có thể cho ăn qua ống</li>
<li>Dùng cho chó & mèo</li>
</ul>
<h3>Khi nào dùng</h3>
<p>Sau phẫu thuật, ốm, biếng ăn, suy dinh dưỡng.</p>'
            ),
        ),
    );
}

/**
 * Seed sản phẩm vào database
 */
function petshop_seed_products() {
    // Kiểm tra quyền
    if (!current_user_can('manage_options')) {
        return false;
    }
    
    $products = array_merge(petshop_get_sample_products(), petshop_get_specific_products());
    $created = 0;
    $errors = array();
    
    // Bản đồ tên danh mục (key trong code => tên thực tế có thể có)
    $category_name_map = array(
        'giay-dep' => array('Giày dép', 'giay-dep'),
        'non' => array('Nón', 'non'),
        'phu-kien-them' => array('Phụ kiện thêm', 'phu-kien-them'),
        'quan-ao' => array('Quần áo', 'quan-ao'),
        'vong-co' => array('Vòng cổ', 'vong-co'),
        'ca' => array('Cá', 'ca'),
        'chim' => array('Chim', 'chim'),
        'cho' => array('Chó', 'cho'),
        'hamster' => array('Hamster', 'hamster'),
        'meo' => array('Mèo', 'meo'),
        'tho' => array('Thỏ', 'tho'),
        'banh-thuong' => array('Bánh thưởng', 'banh-thuong'),
        'thuc-an-dieu-tri-benh' => array('Thức ăn điều trị bệnh', 'thuc-an-dieu-tri-benh'),
        'thuc-an-hat' => array('Thức ăn hạt', 'thuc-an-hat'),
        'thuc-an-huu-co' => array('Thức ăn hữu cơ', 'thuc-an-huu-co'),
        'thuc-an-uot' => array('Thức ăn ướt', 'thuc-an-uot'),
    );
    
    foreach ($products as $cat_slug => $cat_products) {
        // Lấy term - thử nhiều cách
        $term = get_term_by('slug', $cat_slug, 'product_category');
        
        // Nếu không tìm thấy, thử tìm theo tên
        if (!$term && isset($category_name_map[$cat_slug])) {
            foreach ($category_name_map[$cat_slug] as $possible_name) {
                $term = get_term_by('name', $possible_name, 'product_category');
                if ($term) break;
                // Cũng thử slug
                $term = get_term_by('slug', sanitize_title($possible_name), 'product_category');
                if ($term) break;
            }
        }
        
        if (!$term) {
            $errors[] = "Không tìm thấy danh mục: {$cat_slug}";
            continue;
        }
        
        foreach ($cat_products as $product) {
            // Kiểm tra sản phẩm đã tồn tại chưa (theo SKU)
            $existing = get_posts(array(
                'post_type' => 'product',
                'meta_key' => 'product_sku',
                'meta_value' => $product['sku'],
                'posts_per_page' => 1,
            ));
            
            if (!empty($existing)) {
                continue; // Bỏ qua nếu đã tồn tại
            }
            
            // Tạo sản phẩm mới
            $post_id = wp_insert_post(array(
                'post_title'   => $product['title'],
                'post_content' => $product['content'],
                'post_excerpt' => $product['description'],
                'post_status'  => 'publish',
                'post_type'    => 'product',
            ));
            
            if (is_wp_error($post_id)) {
                $errors[] = "Lỗi tạo sản phẩm: {$product['title']}";
                continue;
            }
            
            // Thêm meta data
            update_post_meta($post_id, 'product_price', $product['price']);
            update_post_meta($post_id, 'product_sale_price', $product['sale_price']);
            update_post_meta($post_id, 'product_sku', $product['sku']);
            update_post_meta($post_id, 'product_stock', $product['stock']);
            
            // Thêm meta data cho các bộ lọc
            update_post_meta($post_id, 'product_views', rand(50, 5000)); // Lượt xem ngẫu nhiên
            update_post_meta($post_id, 'product_sold', rand(5, 500)); // Số lượng bán ngẫu nhiên
            update_post_meta($post_id, 'product_rating', number_format(rand(35, 50) / 10, 1)); // Rating 3.5-5.0
            
            // Gán danh mục
            wp_set_object_terms($post_id, $term->term_id, 'product_category');
            
            // Tải và đặt ảnh đại diện
            $image_id = petshop_download_image($product['image'], $post_id);
            if ($image_id) {
                set_post_thumbnail($post_id, $image_id);
            }
            
            $created++;
        }
    }
    
    return array(
        'created' => $created,
        'errors' => $errors,
    );
}

/**
 * Download ảnh từ URL và attach vào post
 */
function petshop_download_image($url, $post_id) {
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    
    // Tạo tên file unique
    $filename = 'petshop-product-' . $post_id . '-' . time() . '.jpg';
    
    // Download file
    $tmp = download_url($url);
    
    if (is_wp_error($tmp)) {
        return false;
    }
    
    $file_array = array(
        'name'     => $filename,
        'tmp_name' => $tmp,
    );
    
    // Sideload vào Media Library
    $image_id = media_handle_sideload($file_array, $post_id);
    
    // Xóa file tạm nếu có lỗi
    if (is_wp_error($image_id)) {
        @unlink($tmp);
        return false;
    }
    
    return $image_id;
}

/**
 * Thêm trang admin để chạy seed
 */
function petshop_add_seed_page() {
    add_submenu_page(
        'edit.php?post_type=product',
        'Tạo sản phẩm mẫu',
        '🌱 Seed sản phẩm',
        'manage_options',
        'petshop-seed-products',
        'petshop_seed_products_page'
    );
}
add_action('admin_menu', 'petshop_add_seed_page');

// =============================================
// AJAX: Lấy thống kê SP hiện có mỗi danh mục
// =============================================
add_action('wp_ajax_petshop_seed_get_stats', function() {
    if (!current_user_can('manage_options')) wp_send_json_error();
    $terms = get_terms(array('taxonomy' => 'product_category', 'hide_empty' => false));
    $stats = array();
    foreach ($terms as $term) {
        $count = (int) wp_count_posts('product')->publish;
        // Đếm chính xác theo taxonomy
        $q = new WP_Query(array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'tax_query' => array(array('taxonomy' => 'product_category', 'field' => 'term_id', 'terms' => $term->term_id)),
        ));
        $stats[$term->term_id] = array(
            'slug'   => $term->slug,
            'name'   => $term->name,
            'count'  => $q->found_posts,
            'parent' => $term->parent,
        );
        wp_reset_postdata();
    }
    wp_send_json_success($stats);
});

// =============================================
// AJAX: Tạo N sản phẩm cho 1 danh mục (batch)
// =============================================
add_action('wp_ajax_petshop_seed_category', function() {
    if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
    check_ajax_referer('petshop_seed_nonce', 'nonce');
    set_time_limit(120);

    $term_id   = intval($_POST['term_id']);
    $mode      = sanitize_text_field($_POST['mode']);   // 'random' | 'add' | 'delete' | 'delete_all'
    $amount    = max(1, intval($_POST['amount'] ?? 5));
    $offset    = intval($_POST['offset'] ?? 0);         // for pagination
    $batch     = min(3, $amount - $offset);             // 3 SP per request

    $term = get_term($term_id, 'product_category');
    if (!$term || is_wp_error($term)) wp_send_json_error('Danh mục không tồn tại');

    // --- DELETE modes ---
    if ($mode === 'delete_all') {
        $posts = get_posts(array(
            'post_type' => 'product', 'posts_per_page' => -1, 'fields' => 'ids',
            'tax_query' => array(array('taxonomy' => 'product_category', 'field' => 'term_id', 'terms' => $term_id)),
        ));
        foreach ($posts as $pid) wp_delete_post($pid, true);
        wp_send_json_success(array('deleted' => count($posts), 'done' => true));
    }
    if ($mode === 'delete_n') {
        $posts = get_posts(array(
            'post_type' => 'product', 'posts_per_page' => $amount, 'fields' => 'ids',
            'tax_query' => array(array('taxonomy' => 'product_category', 'field' => 'term_id', 'terms' => $term_id)),
            'orderby' => 'date', 'order' => 'DESC',
        ));
        foreach ($posts as $pid) wp_delete_post($pid, true);
        wp_send_json_success(array('deleted' => count($posts), 'done' => true));
    }

    // --- CREATE modes (random / add) ---
    // Nếu mode=random, xóa hết trước (chỉ batch đầu offset=0)
    if ($mode === 'random' && $offset === 0) {
        $old = get_posts(array(
            'post_type' => 'product', 'posts_per_page' => -1, 'fields' => 'ids',
            'tax_query' => array(array('taxonomy' => 'product_category', 'field' => 'term_id', 'terms' => $term_id)),
        ));
        foreach ($old as $pid) wp_delete_post($pid, true);
    }

    $created = 0;
    $data = petshop_generate_product_data($term->slug, $term->name, $amount, $offset);

    for ($i = 0; $i < $batch && isset($data[$i]); $i++) {
        $p = $data[$i];
        $post_id = wp_insert_post(array(
            'post_title'   => $p['title'],
            'post_content' => $p['content'],
            'post_excerpt' => $p['description'],
            'post_status'  => 'publish',
            'post_type'    => 'product',
        ));
        if (is_wp_error($post_id)) continue;

        update_post_meta($post_id, 'product_price',      $p['price']);
        update_post_meta($post_id, 'product_sale_price', $p['sale_price']);
        update_post_meta($post_id, 'product_sku',        $p['sku']);
        update_post_meta($post_id, 'product_stock',      $p['stock']);
        update_post_meta($post_id, 'product_views',      rand(30, 5000));
        update_post_meta($post_id, 'product_sold',       rand(5, 500));
        update_post_meta($post_id, 'product_rating',     number_format(rand(35, 50)/10, 1));
        wp_set_object_terms($post_id, $term_id, 'product_category');

        $img_id = petshop_download_image($p['image'], $post_id);
        if ($img_id) set_post_thumbnail($post_id, $img_id);

        $created++;
    }

    $next_offset = $offset + $batch;
    $done = ($next_offset >= $amount);

    wp_send_json_success(array(
        'created'     => $created,
        'next_offset' => $next_offset,
        'done'        => $done,
        'total'       => $amount,
    ));
});

// =============================================
// AJAX: Bulk seed nhiều danh mục
// =============================================
add_action('wp_ajax_petshop_seed_bulk', function() {
    if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
    check_ajax_referer('petshop_seed_nonce', 'nonce');
    set_time_limit(300);

    $mode     = sanitize_text_field($_POST['mode']);   // 'random_all' | 'add_all' | 'delete_all' | 'delete_n'
    $term_ids = array_map('intval', (array)($_POST['term_ids'] ?? array()));
    $amount   = max(1, intval($_POST['amount'] ?? 5));
    $offset   = intval($_POST['offset'] ?? 0);
    $batch    = 2; // 2 danh mục mỗi request

    if (empty($term_ids)) {
        $all_terms = get_terms(array('taxonomy' => 'product_category', 'hide_empty' => false, 'fields' => 'ids'));
        $term_ids  = array_map('intval', $all_terms);
    }

    $chunk      = array_slice($term_ids, $offset, $batch);
    $created    = 0;
    $deleted    = 0;

    foreach ($chunk as $term_id) {
        $term = get_term($term_id, 'product_category');
        if (!$term || is_wp_error($term)) continue;

        if ($mode === 'delete_all') {
            $posts = get_posts(array('post_type'=>'product','posts_per_page'=>-1,'fields'=>'ids',
                'tax_query'=>array(array('taxonomy'=>'product_category','field'=>'term_id','terms'=>$term_id))));
            foreach ($posts as $pid) { wp_delete_post($pid, true); $deleted++; }
            continue;
        }
        if ($mode === 'delete_n') {
            $posts = get_posts(array('post_type'=>'product','posts_per_page'=>$amount,'fields'=>'ids',
                'orderby'=>'date','order'=>'DESC',
                'tax_query'=>array(array('taxonomy'=>'product_category','field'=>'term_id','terms'=>$term_id))));
            foreach ($posts as $pid) { wp_delete_post($pid, true); $deleted++; }
            continue;
        }
        // create modes
        if ($mode === 'random_all') {
            $old = get_posts(array('post_type'=>'product','posts_per_page'=>-1,'fields'=>'ids',
                'tax_query'=>array(array('taxonomy'=>'product_category','field'=>'term_id','terms'=>$term_id))));
            foreach ($old as $pid) wp_delete_post($pid, true);
        }
        $data = petshop_generate_product_data($term->slug, $term->name, $amount, 0);
        foreach (array_slice($data, 0, $amount) as $p) {
            $post_id = wp_insert_post(array(
                'post_title'  =>$p['title'],'post_content'=>$p['content'],
                'post_excerpt'=>$p['description'],'post_status'=>'publish','post_type'=>'product',
            ));
            if (is_wp_error($post_id)) continue;
            update_post_meta($post_id,'product_price',$p['price']);
            update_post_meta($post_id,'product_sale_price',$p['sale_price']);
            update_post_meta($post_id,'product_sku',$p['sku']);
            update_post_meta($post_id,'product_stock',$p['stock']);
            update_post_meta($post_id,'product_views',rand(30,5000));
            update_post_meta($post_id,'product_sold',rand(5,500));
            update_post_meta($post_id,'product_rating',number_format(rand(35,50)/10,1));
            wp_set_object_terms($post_id,$term_id,'product_category');
            $img_id = petshop_download_image($p['image'],$post_id);
            if ($img_id) set_post_thumbnail($post_id,$img_id);
            $created++;
        }
    }

    $next_offset = $offset + $batch;
    $done        = ($next_offset >= count($term_ids));

    wp_send_json_success(array(
        'created'     => $created,
        'deleted'     => $deleted,
        'next_offset' => $next_offset,
        'done'        => $done,
        'total_cats'  => count($term_ids),
    ));
});

// =============================================
// Helper: Sinh dữ liệu sản phẩm thực tế theo danh mục
// =============================================
function petshop_generate_product_data($cat_slug, $cat_name, $count, $offset = 0) {
    // Pool ảnh theo chủ đề
    $image_pools = array(
        'cho'      => array('1587300003388-59208cc962cb','1551717743-5ff94fabe869','1548199973-ec979b45851e','1518717758536-85ae4e2f2d2c'),
        'meo'      => array('1514888286974-6c03e2ca1dba','1574158622682-e40e69881006','1529778873920-4da4926a72c5','1513245467-d1dfde9e3510'),
        'ca'       => array('1522069169874-c58ec4b76be5','1520302630591-fd1c66edc19d','1544568100-847a948585b9','1535591273-ce11e1f2ec20'),
        'chim'     => array('1552728089-57bdde30beb3','1555169062-a7d49e5e6879','1444464213950-8c7c4ee8f9c6','1522661067-b6c4e0063d79'),
        'hamster'  => array('1583511655857-d19b40a7a54e','1589924691995-400dc9ecc119','1548199973-ec979b45851e','1518717758536-85ae4e2f2d2c'),
        'tho'      => array('1589924691995-400dc9ecc119','1535591273-ce11e1f2ec20','1583511655857-d19b40a7a54e','1529778873920-4da4926a72c5'),
        'default'  => array('1587300003388-59208cc962cb','1544568100-847a948585b9','1514888286974-6c03e2ca1dba','1520302630591-fd1c66edc19d','1522069169874-c58ec4b76be5'),
    );
    $pool_key = isset($image_pools[$cat_slug]) ? $cat_slug : 'default';
    $img_pool = $image_pools[$pool_key];

    // Pool tên sản phẩm theo danh mục
    $name_templates = array(
        'vong-co'   => array('Vòng cổ da bò cao cấp','Vòng cổ nylon phản quang','Vòng cổ LED sạc USB','Vòng cổ thêu hoa văn','Vòng cổ dây dù chống nước','Vòng cổ kim loại inox','Vòng cổ da lộn mềm','Vòng cổ có ID tag khắc tên','Vòng cổ chống bọ chét','Vòng cổ GPS tracker'),
        'giay-dep'  => array('Giày bảo vệ chân mùa đông','Giày cao su chống trơn','Dép đi trong nhà cho chó','Giày vải thoáng khí','Boot đi mưa chống thấm','Giày da nhẹ đi dạo','Giày chạy bộ cho chó','Giày silicon mềm','Giày chống nóng mặt đường','Giày bảo vệ vết thương'),
        'non'       => array('Nón vải lưới thoáng mát','Nón mùa hè có gắn quai','Mũ rộng vành chống nắng UV','Nón capuche lông mịn','Mũ len mùa đông cute','Nón thể thao phản quang','Mũ dã ngoại chống mưa','Nón dệt kim có tai','Mũ Halloween hóa trang','Nón da nhỏ thời trang'),
        'quan-ao'   => array('Áo hoodie ấm mùa đông','Áo thun cotton mềm mại','Bộ đồ ngủ kẻ sọc','Áo khoác dạ chống lạnh','Váy công chúa dễ thương','Áo mưa trong suốt','Bộ pyjama in hình xương','Áo gi lê mỏng mùa thu','Jumpsuit kéo khóa tiện lợi','Áo gile lông ấm áp'),
        'phu-kien-them' => array('Dây kéo dài 3m tự động','Dây xích inox 5mm','Dây đai ngực có đệm','Khai cổ gắn tag tên','Móc khóa thẻ nhận dạng','Túi đựng thú cưng du lịch','Ba lô cửa sổ trong suốt','Xe đẩy 4 bánh gập gọn','Địu vải thoáng khí','Lồng nhựa vận chuyển hàng không'),
        'cho'       => array('Đồ chơi nhai cao su thiên nhiên','Xương cao su giả','Bóng phát âm thanh','Đồ chơi kéo co dây thừng','Gương đào tạo thú cưng','Bánh thưởng huấn luyện','Cổng nhai hình xương','Búp bê nhồi bông squeak','Puzzle ăn chậm lại','Frisbee nhựa dẻo'),
        'meo'       => array('Trụ cào móng sisal cao 60cm','Đồ chơi cần câu lông vũ','Nhà hang chui mèo','Đường hầm vải gấp gọn','Bóng chuông lắc tay','Chuột điều khiển từ xa','Đồ chơi laser tự động','Bàn phím giả cho mèo','Băng hạt catnip','Gương nhìn trộm'),
        'thuc-an-hat' => array('Royal Canin Adult 2kg','Pro Plan Sensitive 3kg','Purina Dog Chow 5kg','Orijen Original 1.8kg','Acana Pacifica 2kg','Josera Active 4kg','Hill\'s Science Diet 3kg','Eukanuba Adult 3kg','Taste of Wild 2kg','Blue Buffalo Life 1.5kg'),
        'banh-thuong' => array('Snack thưởng huấn luyện 500g','Que thưởng vị gà sấy','Pate thưởng tuýp 4 hộp','Bánh quy xương nhỏ túi 200g','Snack cá hồi tự nhiên','Dải thưởng mềm vị gan','Snack nhai dental care','Bánh thưởng ít calorie','Kẹo thưởng hạ nhiệt mùa hè','Snack bơ mật ong'),
        'default'   => array('Sản phẩm chất lượng cao','Sản phẩm nhập khẩu chính hãng','Sản phẩm organic an toàn','Sản phẩm vet recommend','Sản phẩm best seller','Sản phẩm mới nhập kho','Sản phẩm giảm giá đặc biệt','Sản phẩm phổ biến','Sản phẩm hot tháng này','Sản phẩm được yêu thích'),
    );
    $name_key = isset($name_templates[$cat_slug]) ? $cat_slug : 'default';
    $names    = $name_templates[$name_key];

    // Mô tả phong phú theo danh mục
    $desc_templates = array(
        'vong-co'   => array('Vòng cổ bền đẹp, an toàn tuyệt đối cho thú cưng. Thiết kế hiện đại, dễ điều chỉnh kích cỡ.','Chất liệu cao cấp, khóa chắc chắn, phù hợp mọi kích thước thú cưng từ nhỏ đến lớn.','Vòng cổ thời trang và công năng cao, được hàng ngàn khách hàng tin dùng.'),
        'giay-dep'  => array('Bảo vệ đôi chân thú cưng trong mọi địa hình. Đế chống trơn, vải thoáng khí, dễ mang.','Giữ ấm và bảo vệ chân khỏi mặt đường nóng/lạnh. Dễ mang tháo, thiết kế cute.','Chân thú cưng sẽ được bảo vệ tốt nhất với đôi giày chuyên dụng này.'),
        'quan-ao'   => array('Giữ ấm hiệu quả trong mùa lạnh, chất liệu mềm mịn không gây kích ứng da.','Thời trang và thoải mái, thiết kế vừa vặn tôn dáng thú cưng của bạn.','Chất liệu co giãn 4 chiều, dễ mặc tháo, phù hợp mọi hoạt động.'),
        'default'   => array('Sản phẩm chất lượng cao, được kiểm định kỹ càng, an toàn cho thú cưng.','Nhập khẩu chính hãng, có chứng nhận chất lượng quốc tế. Được bác sĩ thú y khuyến dùng.','Thiết kế thông minh, tiện dụng. Hàng ngàn khách hàng đã tin dùng và hài lòng.'),
    );
    $desc_key = isset($desc_templates[$cat_slug]) ? $cat_slug : 'default';

    // Content HTML chi tiết
    $content_base = array(
        "<h3>🌟 Điểm nổi bật</h3><ul><li>Chất lượng cao cấp, bền đẹp</li><li>An toàn 100% cho thú cưng</li><li>Thiết kế ergonomic phù hợp thú cưng</li><li>Dễ vệ sinh, bảo quản</li></ul><h3>📦 Quy cách đóng gói</h3><p>Đóng hộp cẩn thận, có hướng dẫn sử dụng tiếng Việt.</p><h3>🎁 Cam kết</h3><p>Hoàn tiền 100% nếu sản phẩm lỗi do nhà sản xuất trong 30 ngày.</p>",
        "<h3>📋 Thông số kỹ thuật</h3><ul><li>Vật liệu: Cao cấp, không độc hại</li><li>Kích thước: Nhiều size từ XS đến XL</li><li>Trọng lượng: Nhẹ, không gây bất tiện</li><li>Màu sắc: Đa dạng lựa chọn</li></ul><h3>✅ Phù hợp với</h3><p>Chó, mèo mọi giống và mọi lứa tuổi.</p>",
        "<h3>💡 Hướng dẫn sử dụng</h3><p>Rất dễ sử dụng, không cần kỹ năng đặc biệt. Xem hướng dẫn kèm theo.</p><h3>🧼 Vệ sinh & bảo quản</h3><ul><li>Rửa tay trước và sau khi dùng</li><li>Vệ sinh định kỳ bằng nước ấm</li><li>Bảo quản nơi khô ráo, thoáng mát</li></ul>",
    );

    $products = array();
    for ($i = 0; $i < $count; $i++) {
        $idx      = ($offset + $i);
        $name_idx = $idx % count($names);
        $name     = $names[$name_idx];
        // Thêm số thứ tự nếu trùng
        if ($idx >= count($names)) $name .= ' ' . (floor($idx / count($names)) + 1);

        $price      = rand(50, 500) * 1000;
        $has_sale   = rand(0, 1);
        $sale_price = $has_sale ? round($price * rand(60, 90) / 100 / 1000) * 1000 : 0;

        $sku_prefix = strtoupper(substr(preg_replace('/[^a-z]/i', '', $cat_slug), 0, 3));
        $sku        = $sku_prefix . '-' . str_pad($idx + 1, 3, '0', STR_PAD_LEFT) . '-' . substr(uniqid(), -4);

        $img_id = $img_pool[$idx % count($img_pool)];
        $image  = "https://images.unsplash.com/photo-{$img_id}?w=600&q=80";

        $desc_arr = $desc_templates[$desc_key];
        $description = $desc_arr[$idx % count($desc_arr)];
        $content     = $content_base[$idx % count($content_base)];

        $products[] = array(
            'title'       => $name,
            'price'       => $price,
            'sale_price'  => $sale_price,
            'sku'         => $sku,
            'stock'       => rand(10, 200),
            'image'       => $image,
            'description' => $description,
            'content'     => "<h3>Thông tin sản phẩm: {$name}</h3><p><strong>Danh mục:</strong> {$cat_name}</p>" . $content,
        );
    }
    return $products;
}

// =============================================
// Giao diện trang Admin
// =============================================
function petshop_seed_products_page() {
    $nonce = wp_create_nonce('petshop_seed_nonce');
    ?>
    <div class="wrap" id="petshop-seed-wrap">
    <style>
    #petshop-seed-wrap { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
    .seed-page-header { display:flex; align-items:center; gap:14px; margin-bottom:24px; }
    .seed-page-header h1 { margin:0; font-size:1.6rem; color:#5D4E37; }
    .seed-page-header .header-icon { width:44px; height:44px; background:linear-gradient(135deg,#EC802B,#F5994D); border-radius:12px; display:flex; align-items:center; justify-content:center; color:#fff; font-size:1.3rem; }

    /* Bulk toolbar */
    .seed-bulk-bar { background:#fff; border-radius:14px; padding:20px 24px; box-shadow:0 2px 12px rgba(0,0,0,0.07); margin-bottom:24px; display:flex; flex-wrap:wrap; gap:14px; align-items:flex-end; }
    .seed-bulk-bar h2 { margin:0 0 14px; font-size:1.05rem; color:#5D4E37; width:100%; display:flex; align-items:center; gap:8px; }
    .seed-field { display:flex; flex-direction:column; gap:5px; }
    .seed-field label { font-size:0.8rem; font-weight:600; color:#7A6B5A; }
    .seed-field input[type=number], .seed-field select { padding:8px 12px; border:1.5px solid #E8CCAD; border-radius:8px; font-size:0.9rem; color:#5D4E37; width:130px; }
    .seed-btn { display:inline-flex; align-items:center; gap:7px; padding:9px 18px; border:none; border-radius:9px; cursor:pointer; font-weight:600; font-size:0.88rem; transition:all .2s; }
    .seed-btn:hover { transform:translateY(-1px); box-shadow:0 4px 12px rgba(0,0,0,0.15); }
    .seed-btn-primary   { background:linear-gradient(135deg,#EC802B,#F5994D); color:#fff; }
    .seed-btn-add       { background:linear-gradient(135deg,#17a2b8,#20c0d8); color:#fff; }
    .seed-btn-danger    { background:linear-gradient(135deg,#d9534f,#e05b57); color:#fff; }
    .seed-btn-secondary { background:#f0f0f0; color:#555; }
    .seed-btn:disabled  { opacity:.5; cursor:not-allowed; transform:none !important; }

    /* Progress */
    .seed-progress-wrap { display:none; background:#fff; border-radius:14px; padding:20px 24px; box-shadow:0 2px 12px rgba(0,0,0,0.07); margin-bottom:24px; }
    .seed-progress-wrap.active { display:block; }
    .seed-progress-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; }
    .seed-progress-header h3 { margin:0; font-size:1rem; color:#5D4E37; display:flex; align-items:center; gap:8px; }
    .seed-progress-bar-bg { height:16px; background:#f0ebe4; border-radius:20px; overflow:hidden; margin-bottom:8px; }
    .seed-progress-bar-fill { height:100%; background:linear-gradient(90deg,#EC802B,#F5994D); border-radius:20px; transition:width .4s ease; width:0%; }
    .seed-progress-text { font-size:0.85rem; color:#7A6B5A; display:flex; justify-content:space-between; }
    .seed-progress-log  { margin-top:10px; max-height:120px; overflow-y:auto; font-size:0.82rem; color:#666; background:#f8f5f0; border-radius:8px; padding:10px; }

    /* Category grid */
    .seed-cats-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; flex-wrap:wrap; gap:10px; }
    .seed-cats-header h2 { margin:0; font-size:1.05rem; color:#5D4E37; display:flex; align-items:center; gap:8px; }
    .seed-search { padding:8px 12px; border:1.5px solid #E8CCAD; border-radius:8px; font-size:0.88rem; color:#5D4E37; width:200px; }

    .seed-cats-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; }
    @media (max-width:1200px) { .seed-cats-grid { grid-template-columns:repeat(3,1fr); } }
    @media (max-width:900px)  { .seed-cats-grid { grid-template-columns:repeat(2,1fr); } }

    .seed-cat-card { background:#fff; border-radius:14px; box-shadow:0 2px 12px rgba(0,0,0,0.07); padding:18px; transition:all .25s; border:2px solid transparent; }
    .seed-cat-card:hover { box-shadow:0 6px 24px rgba(0,0,0,0.12); transform:translateY(-2px); }
    .seed-cat-card.selected { border-color:#EC802B; }
    .cat-card-top { display:flex; align-items:center; gap:10px; margin-bottom:12px; }
    .cat-card-icon { width:36px; height:36px; border-radius:10px; background:linear-gradient(135deg,#EC802B22,#F5994D22); display:flex; align-items:center; justify-content:center; font-size:1.2rem; }
    .cat-card-name { font-weight:700; color:#5D4E37; font-size:0.95rem; flex:1; }
    .cat-card-count { font-size:0.78rem; background:#FDF8F3; color:#EC802B; padding:3px 10px; border-radius:20px; font-weight:700; }
    .cat-card-slug  { font-size:0.75rem; color:#aaa; margin-bottom:10px; font-family:monospace; }
    .cat-card-actions { display:flex; flex-direction:column; gap:8px; }
    .cat-card-row { display:flex; gap:6px; align-items:center; }
    .cat-card-input { padding:6px 10px; border:1.5px solid #E8CCAD; border-radius:7px; font-size:0.82rem; color:#5D4E37; width:65px; text-align:center; }
    .cat-card-btn { flex:1; padding:7px 8px; border:none; border-radius:7px; cursor:pointer; font-weight:600; font-size:0.8rem; display:inline-flex; align-items:center; justify-content:center; gap:5px; transition:all .2s; }
    .cat-card-btn:hover { filter:brightness(1.08); transform:translateY(-1px); }
    .cat-card-btn.c-primary { background:linear-gradient(135deg,#EC802B,#F5994D); color:#fff; }
    .cat-card-btn.c-add     { background:linear-gradient(135deg,#17a2b8,#20c0d8); color:#fff; }
    .cat-card-btn.c-danger  { background:linear-gradient(135deg,#d9534f,#e05b57); color:#fff; }
    .cat-card-btn.c-dark    { background:#666; color:#fff; }
    .cat-card-btn:disabled  { opacity:.45; cursor:not-allowed; transform:none; }
    .cat-card-status { font-size:0.75rem; color:#66BCB4; font-weight:600; min-height:18px; margin-top:4px; display:flex; align-items:center; gap:4px; }
    .spinning { animation:seedSpin 1s linear infinite; display:inline-block; }
    @keyframes seedSpin { to { transform:rotate(360deg); } }

    /* Select all checkbox */
    .bulk-cat-select { display:flex; align-items:center; gap:8px; font-size:0.85rem; color:#7A6B5A; cursor:pointer; }
    </style>

    <div class="seed-page-header">
        <div class="header-icon">🌱</div>
        <h1>Quản lý sản phẩm mẫu</h1>
    </div>

    <!-- BULK TOOLBAR -->
    <div class="seed-bulk-bar">
        <h2><span class="dashicons dashicons-randomize"></span> Thao tác hàng loạt</h2>

        <div class="seed-field">
            <label>Số SP / danh mục</label>
            <input type="number" id="bulk-amount" value="5" min="1" max="50">
        </div>

        <div class="seed-field">
            <label>Áp dụng cho</label>
            <select id="bulk-scope">
                <option value="selected">Đã chọn</option>
                <option value="all" selected>Tất cả danh mục</option>
            </select>
        </div>

        <button class="seed-btn seed-btn-primary" onclick="bulkAction('random_all')">
            <span class="dashicons dashicons-update"></span> Tạo lại toàn bộ
        </button>
        <button class="seed-btn seed-btn-add" onclick="bulkAction('add_all')">
            <span class="dashicons dashicons-plus-alt"></span> Thêm sản phẩm
        </button>
        <div class="seed-field">
            <label>Xóa N sản phẩm gần nhất</label>
            <input type="number" id="bulk-delete-n" value="5" min="1" max="9999" style="width:90px;">
        </div>
        <button class="seed-btn seed-btn-danger" onclick="bulkAction('delete_n')">
            <span class="dashicons dashicons-trash"></span> Xóa N SP
        </button>
        <button class="seed-btn seed-btn-danger" onclick="bulkAction('delete_all')">
            <span class="dashicons dashicons-dismiss"></span> Xóa tất cả SP
        </button>
    </div>

    <!-- PROGRESS BAR -->
    <div class="seed-progress-wrap" id="progressWrap">
        <div class="seed-progress-header">
            <h3><span class="spinning dashicons dashicons-update"></span> <span id="progress-title">Đang xử lý...</span></h3>
            <button class="seed-btn seed-btn-secondary" onclick="stopSeed()">
                <span class="dashicons dashicons-no-alt"></span> Dừng lại
            </button>
        </div>
        <div class="seed-progress-bar-bg">
            <div class="seed-progress-bar-fill" id="progressFill"></div>
        </div>
        <div class="seed-progress-text">
            <span id="progress-count">0 / 0</span>
            <span id="progress-pct">0%</span>
        </div>
        <div class="seed-progress-log" id="progressLog"></div>
    </div>

    <!-- CATEGORY GRID -->
    <div class="seed-cats-header">
        <h2><span class="dashicons dashicons-grid-view"></span> Danh mục sản phẩm</h2>
        <div style="display:flex;gap:10px;align-items:center;">
            <label class="bulk-cat-select">
                <input type="checkbox" id="selectAllCats" onchange="toggleSelectAll(this)"> Chọn tất cả
            </label>
            <input type="text" class="seed-search" id="catSearch" placeholder="🔍 Tìm danh mục..." oninput="filterCards()">
            <button class="seed-btn seed-btn-secondary" onclick="refreshStats()">
                <span class="dashicons dashicons-update"></span> Làm mới số liệu
            </button>
        </div>
    </div>

    <div class="seed-cats-grid" id="catGrid">
        <div style="grid-column:1/-1;text-align:center;padding:40px;color:#aaa;">
            <span class="dashicons dashicons-update spinning" style="font-size:2rem;"></span>
            <p>Đang tải danh mục...</p>
        </div>
    </div>
    </div><!-- /wrap -->

    <script>
    const SEED_NONCE = '<?php echo esc_js($nonce); ?>';
    const AJAX_URL   = ajaxurl;
    let stopFlag     = false;
    let allTerms     = {};

    // ---- Icon map ----
    const catIcons = {
        'cho':'🐕','meo':'🐱','ca':'🐟','chim':'🦜','hamster':'🐹','tho':'🐇',
        'vong-co':'📿','giay-dep':'👟','non':'🧢','quan-ao':'👕','phu-kien-them':'🎒',
        'banh-thuong':'🍖','thuc-an-hat':'🌾','thuc-an-uot':'🥫','thuc-an-huu-co':'🌿','thuc-an-dieu-tri-benh':'💊',
        'default':'🏷️'
    };
    function getIcon(slug) {
        for (const k in catIcons) if (slug.includes(k)) return catIcons[k];
        return catIcons.default;
    }

    // ---- Load stats & render cards ----
    async function loadStats() {
        const res = await fetch(AJAX_URL + '?action=petshop_seed_get_stats', {credentials:'same-origin'});
        const data = await res.json();
        if (!data.success) return;
        allTerms = data.data;
        renderCards(allTerms);
    }

    function renderCards(terms) {
        const grid = document.getElementById('catGrid');
        const childTerms = Object.values(terms).filter(t => t.parent > 0 || Object.values(terms).every(x => x.parent === 0));
        // Show all terms
        const list = Object.values(terms);
        if (!list.length) { grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:30px;color:#aaa;">Không có danh mục nào.</div>'; return; }
        grid.innerHTML = list.map(t => `
        <div class="seed-cat-card" data-id="${t.slug}" data-term-id="${t.slug}" id="card-${t.slug}">
            <div class="cat-card-top">
                <label style="cursor:pointer;display:flex;align-items:center;gap:0;">
                    <input type="checkbox" class="cat-select-cb" data-tid="${Object.keys(allTerms).find(k=>allTerms[k].slug===t.slug)}" style="margin:0 8px 0 0;">
                </label>
                <div class="cat-card-icon">${getIcon(t.slug)}</div>
                <div class="cat-card-name">${t.name}</div>
                <span class="cat-card-count" id="cnt-${t.slug}">${t.count}</span>
            </div>
            <div class="cat-card-slug">${t.slug}</div>
            <div class="cat-card-actions">
                <div class="cat-card-row">
                    <input type="number" class="cat-card-input cat-n-input" value="5" min="1" max="100" title="Số sản phẩm">
                    <button class="cat-card-btn c-primary" onclick="catAction('${t.slug}','random')">
                        <span class="dashicons dashicons-update"></span> Tạo lại
                    </button>
                    <button class="cat-card-btn c-add" onclick="catAction('${t.slug}','add')">
                        <span class="dashicons dashicons-plus-alt"></span> Thêm
                    </button>
                </div>
                <div class="cat-card-row">
                    <input type="number" class="cat-card-input cat-del-input" value="5" min="1" max="9999" title="Xóa N sản phẩm">
                    <button class="cat-card-btn c-danger" onclick="catAction('${t.slug}','delete_n')">
                        <span class="dashicons dashicons-trash"></span> Xóa N
                    </button>
                    <button class="cat-card-btn c-dark" onclick="catAction('${t.slug}','delete_all')" title="Xóa toàn bộ SP danh mục này">
                        <span class="dashicons dashicons-dismiss"></span> Xóa hết
                    </button>
                </div>
            </div>
            <div class="cat-card-status" id="status-${t.slug}"></div>
        </div>`).join('');
    }

    async function catAction(slug, mode) {
        const term = Object.values(allTerms).find(t => t.slug === slug);
        if (!term) return alert('Không tìm thấy danh mục!');
        const termId = Object.keys(allTerms).find(k => allTerms[k].slug === slug);

        const card    = document.getElementById('card-' + slug);
        const nInput  = card.querySelector('.cat-n-input');
        const dInput  = card.querySelector('.cat-del-input');
        const amount  = mode.startsWith('delete') ? parseInt(dInput.value)||5 : parseInt(nInput.value)||5;
        const statusEl = document.getElementById('status-' + slug);
        const btns    = card.querySelectorAll('.cat-card-btn');

        if (mode === 'delete_all' && !confirm(`Xóa TẤT CẢ sản phẩm trong "${term.name}"?`)) return;

        btns.forEach(b => b.disabled = true);
        statusEl.innerHTML = '<span class="dashicons dashicons-update spinning"></span> Đang xử lý...';
        stopFlag = false;

        if (mode === 'random' || mode === 'add') {
            // Batched creation with progress
            showProgress(`Đang tạo sản phẩm: ${term.name}`, amount);
            let offset = 0, total_created = 0;
            while (offset < amount && !stopFlag) {
                const body = new URLSearchParams({
                    action:'petshop_seed_category', nonce:SEED_NONCE,
                    term_id:termId, mode, amount, offset
                });
                const r = await fetch(AJAX_URL, {method:'POST',credentials:'same-origin',body});
                const d = await r.json();
                if (!d.success) { logProgress('❌ Lỗi: ' + (d.data||'unknown')); break; }
                total_created += d.data.created;
                offset = d.data.next_offset;
                updateProgress(offset, amount, `${term.name}: ${total_created} sản phẩm đã tạo`);
                if (d.data.done) break;
            }
            hideProgress();
            statusEl.innerHTML = `<span class="dashicons dashicons-yes-alt"></span> Đã tạo ${total_created} sản phẩm`;
        } else {
            // Delete
            const body = new URLSearchParams({
                action:'petshop_seed_category', nonce:SEED_NONCE,
                term_id:termId, mode, amount
            });
            const r = await fetch(AJAX_URL, {method:'POST',credentials:'same-origin',body});
            const d = await r.json();
            if (d.success) statusEl.innerHTML = `<span class="dashicons dashicons-trash"></span> Đã xóa ${d.data.deleted} sản phẩm`;
            else statusEl.innerHTML = '❌ Lỗi xóa';
        }

        btns.forEach(b => b.disabled = false);
        await refreshCardCount(slug, termId);
    }

    async function bulkAction(mode) {
        const scope  = document.getElementById('bulk-scope').value;
        const amount = mode === 'delete_n'
            ? parseInt(document.getElementById('bulk-delete-n').value)||5
            : parseInt(document.getElementById('bulk-amount').value)||5;

        let termIds = [];
        if (scope === 'selected') {
            document.querySelectorAll('.cat-select-cb:checked').forEach(cb => termIds.push(cb.dataset.tid));
            if (!termIds.length) { alert('Vui lòng chọn ít nhất 1 danh mục!'); return; }
        }

        const label = {'random_all':'Tạo lại toàn bộ','add_all':'Thêm sản phẩm','delete_n':'Xóa N sản phẩm','delete_all':'Xóa tất cả'}[mode]||mode;
        const totalCats = scope === 'all' ? Object.keys(allTerms).length : termIds.length;
        if (mode.startsWith('delete') && !confirm(`${label} cho ${totalCats} danh mục?`)) return;

        showProgress(`${label}...`, totalCats);
        stopFlag = false;
        let offset = 0, total_created = 0, total_deleted = 0;

        while (!stopFlag) {
            const body = new URLSearchParams({
                action:'petshop_seed_bulk', nonce:SEED_NONCE,
                mode, amount, offset,
                'term_ids[]': termIds
            });
            const r = await fetch(AJAX_URL, {method:'POST',credentials:'same-origin',body});
            const d = await r.json();
            if (!d.success) { logProgress('❌ ' + (d.data||'Lỗi')); break; }
            total_created += d.data.created||0;
            total_deleted += d.data.deleted||0;
            offset = d.data.next_offset;
            const done = Math.min(offset, totalCats);
            updateProgress(done, totalCats, `Đã xử lý ${done}/${totalCats} danh mục — Tạo: ${total_created} | Xóa: ${total_deleted}`);
            if (d.data.done) break;
        }

        hideProgress();
        await loadStats(); // refresh all counts
    }

    // ---- Progress helpers ----
    function showProgress(title, total) {
        document.getElementById('progressWrap').classList.add('active');
        document.getElementById('progress-title').textContent = title;
        document.getElementById('progressFill').style.width = '0%';
        document.getElementById('progress-count').textContent = `0 / ${total}`;
        document.getElementById('progress-pct').textContent = '0%';
        document.getElementById('progressLog').textContent = '';
    }
    function updateProgress(done, total, msg) {
        const pct = total > 0 ? Math.round(done/total*100) : 0;
        document.getElementById('progressFill').style.width = pct + '%';
        document.getElementById('progress-count').textContent = `${done} / ${total}`;
        document.getElementById('progress-pct').textContent   = pct + '%';
        logProgress(msg);
    }
    function logProgress(msg) {
        const el = document.getElementById('progressLog');
        el.innerHTML += msg + '<br>';
        el.scrollTop = el.scrollHeight;
    }
    function hideProgress() {
        setTimeout(() => document.getElementById('progressWrap').classList.remove('active'), 2000);
    }
    function stopSeed() {
        stopFlag = true;
        document.getElementById('progress-title').textContent = '⛔ Đã dừng';
    }

    // ---- Refresh single card count ----
    async function refreshCardCount(slug, termId) {
        const res = await fetch(AJAX_URL + '?action=petshop_seed_get_stats', {credentials:'same-origin'});
        const d   = await res.json();
        if (!d.success) return;
        allTerms = d.data;
        const t = d.data[termId] || Object.values(d.data).find(x => x.slug === slug);
        if (t) document.getElementById('cnt-' + slug).textContent = t.count;
    }

    async function refreshStats() {
        document.getElementById('catGrid').innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:30px;color:#aaa;"><span class="dashicons dashicons-update spinning" style="font-size:2rem;"></span></div>';
        await loadStats();
    }

    // ---- Search filter ----
    function filterCards() {
        const q = document.getElementById('catSearch').value.toLowerCase();
        document.querySelectorAll('.seed-cat-card').forEach(c => {
            c.style.display = (!q || c.dataset.id.includes(q) || c.querySelector('.cat-card-name').textContent.toLowerCase().includes(q)) ? '' : 'none';
        });
    }

    // ---- Select all ----
    function toggleSelectAll(cb) {
        document.querySelectorAll('.cat-select-cb').forEach(c => c.checked = cb.checked);
        document.querySelectorAll('.seed-cat-card').forEach(c => c.classList.toggle('selected', cb.checked));
    }

    document.addEventListener('change', e => {
        if (e.target.classList.contains('cat-select-cb')) {
            e.target.closest('.seed-cat-card').classList.toggle('selected', e.target.checked);
        }
    });

    // ---- Init ----
    loadStats();
    </script>
    <?php
}