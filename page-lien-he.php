<?php
/**
 * Template Name: Trang Liên Hệ
 * @package PetShop
 */
get_header(); ?>

<!-- Page Header -->
<section class="page-header" style="background:#FDF8F3;padding:60px 0;text-align:center;border-bottom:2px solid #F5EDE0;">
    <div class="container">
        <h1 class="page-title" style="font-size:2.5rem;font-weight:800;margin:0 0 10px;color:#5D4E37;display:flex;align-items:center;justify-content:center;gap:12px;">
            <i class="bi bi-telephone-fill" style="color:#EC802B;"></i> Liên Hệ
        </h1>
        <p style="font-size:1.1rem;color:#7A6B5A;margin:0;">Chúng tôi luôn sẵn sàng hỗ trợ bạn</p>
    </div>
</section>

<!-- Breadcrumb -->
<section style="background:#FDF8F3;padding:14px 0;border-bottom:1px solid #F5EDE0;">
    <div class="container">
        <nav style="font-size:.9rem;display:flex;align-items:center;gap:8px;">
            <a href="<?php echo home_url(); ?>" style="color:#5D4E37;text-decoration:none;display:flex;align-items:center;gap:4px;">
                <i class="bi bi-house-fill"></i> Trang chủ
            </a>
            <i class="bi bi-chevron-right" style="color:#bbb;font-size:.75rem;"></i>
            <span style="color:#EC802B;font-weight:600;">Liên hệ</span>
        </nav>
    </div>
</section>

<main class="main-content" style="background:#FDF8F3;">

    <!-- Contact Info Cards -->
    <section style="padding:60px 0;">
        <div class="container">
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin-bottom:60px;">

                <!-- Địa chỉ -->
                <a href="https://maps.google.com/?q=123+Đường+ABC,+Quận+1,+TP+Hồ+Chí+Minh"
                   target="_blank" rel="noopener"
                   style="background:#fff;border-radius:20px;padding:30px 20px;text-align:center;box-shadow:0 5px 25px rgba(93,78,55,.08);text-decoration:none;display:block;border:2px solid transparent;transition:all .25s;"
                   onmouseover="this.style.borderColor='#EC802B';this.style.transform='translateY(-5px)';"
                   onmouseout="this.style.borderColor='transparent';this.style.transform='';">
                    <div style="width:64px;height:64px;background:linear-gradient(135deg,#EC802B,#F5994D);border-radius:18px;display:flex;align-items:center;justify-content:center;margin:0 auto 18px;box-shadow:0 6px 20px rgba(236,128,43,.3);">
                        <i class="bi bi-geo-alt-fill" style="font-size:1.7rem;color:#fff;"></i>
                    </div>
                    <h3 style="font-size:1rem;font-weight:700;color:#5D4E37;margin:0 0 8px;">Địa chỉ</h3>
                    <p style="color:#7A6B5A;font-size:.9rem;line-height:1.6;margin:0;">123 Đường ABC, Quận 1<br>TP. Hồ Chí Minh</p>
                    <span style="display:inline-flex;align-items:center;gap:4px;margin-top:10px;font-size:.8rem;color:#EC802B;font-weight:600;">
                        <i class="bi bi-map"></i> Xem bản đồ
                    </span>
                </a>

                <!-- Điện thoại -->
                <div style="background:#fff;border-radius:20px;padding:30px 20px;text-align:center;box-shadow:0 5px 25px rgba(93,78,55,.08);border:2px solid transparent;transition:all .25s;"
                     onmouseover="this.style.borderColor='#EC802B';this.style.transform='translateY(-5px)';"
                     onmouseout="this.style.borderColor='transparent';this.style.transform='';">
                    <div style="width:64px;height:64px;background:linear-gradient(135deg,#EC802B,#F5994D);border-radius:18px;display:flex;align-items:center;justify-content:center;margin:0 auto 18px;box-shadow:0 6px 20px rgba(236,128,43,.3);">
                        <i class="bi bi-telephone-fill" style="font-size:1.7rem;color:#fff;"></i>
                    </div>
                    <h3 style="font-size:1rem;font-weight:700;color:#5D4E37;margin:0 0 8px;">Điện thoại</h3>
                    <a href="tel:0123456789" style="display:block;color:#EC802B;font-weight:700;text-decoration:none;font-size:.95rem;margin-bottom:4px;">
                        <i class="bi bi-telephone"></i> 0123 456 789
                    </a>
                    <a href="tel:0987654321" style="display:block;color:#7A6B5A;font-weight:600;text-decoration:none;font-size:.88rem;">
                        <i class="bi bi-headset"></i> 0987 654 321
                    </a>
                </div>

                <!-- Email -->
                <div style="background:#fff;border-radius:20px;padding:30px 20px;text-align:center;box-shadow:0 5px 25px rgba(93,78,55,.08);border:2px solid transparent;transition:all .25s;"
                     onmouseover="this.style.borderColor='#EC802B';this.style.transform='translateY(-5px)';"
                     onmouseout="this.style.borderColor='transparent';this.style.transform='';">
                    <div style="width:64px;height:64px;background:linear-gradient(135deg,#EC802B,#F5994D);border-radius:18px;display:flex;align-items:center;justify-content:center;margin:0 auto 18px;box-shadow:0 6px 20px rgba(236,128,43,.3);">
                        <i class="bi bi-envelope-fill" style="font-size:1.7rem;color:#fff;"></i>
                    </div>
                    <h3 style="font-size:1rem;font-weight:700;color:#5D4E37;margin:0 0 8px;">Email</h3>
                    <a href="mailto:info@petshop.com" style="display:block;color:#EC802B;font-weight:700;text-decoration:none;font-size:.9rem;margin-bottom:4px;">
                        <i class="bi bi-envelope"></i> info@petshop.com
                    </a>
                    <a href="mailto:support@petshop.com" style="display:block;color:#7A6B5A;font-weight:600;text-decoration:none;font-size:.85rem;">
                        <i class="bi bi-envelope"></i> support@petshop.com
                    </a>
                </div>

                <!-- Giờ làm việc -->
                <div style="background:#fff;border-radius:20px;padding:30px 20px;text-align:center;box-shadow:0 5px 25px rgba(93,78,55,.08);border:2px solid transparent;transition:all .25s;"
                     onmouseover="this.style.borderColor='#EC802B';this.style.transform='translateY(-5px)';"
                     onmouseout="this.style.borderColor='transparent';this.style.transform='';">
                    <div style="width:64px;height:64px;background:linear-gradient(135deg,#EC802B,#F5994D);border-radius:18px;display:flex;align-items:center;justify-content:center;margin:0 auto 18px;box-shadow:0 6px 20px rgba(236,128,43,.3);">
                        <i class="bi bi-clock-fill" style="font-size:1.7rem;color:#fff;"></i>
                    </div>
                    <h3 style="font-size:1rem;font-weight:700;color:#5D4E37;margin:0 0 8px;">Giờ làm việc</h3>
                    <p style="color:#7A6B5A;font-size:.9rem;line-height:1.8;margin:0;">
                        <strong style="color:#5D4E37;">T2 – T7:</strong> 8:00 – 21:00<br>
                        <strong style="color:#5D4E37;">Chủ nhật:</strong> 9:00 – 18:00
                    </p>
                </div>
            </div>

            <!-- Form & Map -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:40px;">

                <!-- Form gửi tin nhắn -->
                <div style="background:#fff;padding:40px;border-radius:24px;box-shadow:0 5px 25px rgba(93,78,55,.08);">
                    <h2 style="font-size:1.6rem;font-weight:800;color:#5D4E37;margin:0 0 8px;display:flex;align-items:center;gap:10px;">
                        <i class="bi bi-chat-dots-fill" style="color:#EC802B;"></i> Gửi tin nhắn
                    </h2>
                    <p style="color:#7A6B5A;margin:0 0 28px;">Để lại thông tin, chúng tôi sẽ phản hồi trong 24 giờ!</p>

                    <form id="contactForm" class="contact-form">
                        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('petshop_contact_form'); ?>">
                        <div id="contactMessage" style="display:none;margin-bottom:20px;"></div>

                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
                            <div>
                                <label style="display:flex;align-items:center;gap:6px;font-weight:600;color:#5D4E37;font-size:.88rem;margin-bottom:6px;">
                                    <i class="bi bi-person-fill" style="color:#EC802B;"></i> Họ tên <span style="color:#e74c3c;">*</span>
                                </label>
                                <input type="text" name="name" id="contactName" required placeholder="Nguyễn Văn A"
                                       style="width:100%;padding:12px 14px;border:1.5px solid #E8CCAD;border-radius:10px;font-size:.95rem;outline:none;box-sizing:border-box;transition:border-color .2s;"
                                       onfocus="this.style.borderColor='#EC802B'" onblur="this.style.borderColor='#E8CCAD'">
                            </div>
                            <div>
                                <label style="display:flex;align-items:center;gap:6px;font-weight:600;color:#5D4E37;font-size:.88rem;margin-bottom:6px;">
                                    <i class="bi bi-telephone-fill" style="color:#EC802B;"></i> Số điện thoại <span style="color:#e74c3c;">*</span>
                                </label>
                                <input type="tel" name="phone" id="contactPhone" required placeholder="0909 xxx xxx"
                                       style="width:100%;padding:12px 14px;border:1.5px solid #E8CCAD;border-radius:10px;font-size:.95rem;outline:none;box-sizing:border-box;transition:border-color .2s;"
                                       onfocus="this.style.borderColor='#EC802B'" onblur="this.style.borderColor='#E8CCAD'">
                            </div>
                        </div>

                        <div style="margin-bottom:16px;">
                            <label style="display:flex;align-items:center;gap:6px;font-weight:600;color:#5D4E37;font-size:.88rem;margin-bottom:6px;">
                                <i class="bi bi-envelope-fill" style="color:#EC802B;"></i> Email
                            </label>
                            <input type="email" name="email" id="contactEmail" placeholder="email@example.com"
                                   style="width:100%;padding:12px 14px;border:1.5px solid #E8CCAD;border-radius:10px;font-size:.95rem;outline:none;box-sizing:border-box;transition:border-color .2s;"
                                   onfocus="this.style.borderColor='#EC802B'" onblur="this.style.borderColor='#E8CCAD'">
                        </div>

                        <div style="margin-bottom:16px;">
                            <label style="display:flex;align-items:center;gap:6px;font-weight:600;color:#5D4E37;font-size:.88rem;margin-bottom:6px;">
                                <i class="bi bi-tag-fill" style="color:#EC802B;"></i> Chủ đề
                            </label>
                            <select name="subject" id="contactSubject"
                                    style="width:100%;padding:12px 14px;border:1.5px solid #E8CCAD;border-radius:10px;font-size:.95rem;outline:none;box-sizing:border-box;background:#fff;transition:border-color .2s;"
                                    onfocus="this.style.borderColor='#EC802B'" onblur="this.style.borderColor='#E8CCAD'">
                                <option value="Liên hệ chung">Liên hệ chung</option>
                                <option value="Tư vấn sản phẩm">Tư vấn sản phẩm</option>
                                <option value="Đặt lịch dịch vụ">Đặt lịch dịch vụ</option>
                                <option value="Khiếu nại / Góp ý">Khiếu nại / Góp ý</option>
                                <option value="Hợp tác kinh doanh">Hợp tác kinh doanh</option>
                            </select>
                        </div>

                        <div style="margin-bottom:24px;">
                            <label style="display:flex;align-items:center;gap:6px;font-weight:600;color:#5D4E37;font-size:.88rem;margin-bottom:6px;">
                                <i class="bi bi-chat-left-text-fill" style="color:#EC802B;"></i> Nội dung <span style="color:#e74c3c;">*</span>
                            </label>
                            <textarea name="message" id="contactMessageInput" rows="4" required placeholder="Nhập nội dung tin nhắn..."
                                      style="width:100%;padding:12px 14px;border:1.5px solid #E8CCAD;border-radius:10px;font-size:.95rem;outline:none;box-sizing:border-box;resize:vertical;transition:border-color .2s;font-family:inherit;"
                                      onfocus="this.style.borderColor='#EC802B'" onblur="this.style.borderColor='#E8CCAD'"></textarea>
                        </div>

                        <button type="submit" id="contactSubmitBtn"
                                style="width:100%;padding:14px;background:linear-gradient(135deg,#EC802B,#F5994D);color:#fff;border:none;border-radius:12px;font-size:1rem;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;transition:all .2s;box-shadow:0 4px 15px rgba(236,128,43,.35);">
                            <i class="bi bi-send-fill"></i> Gửi tin nhắn
                        </button>
                    </form>
                </div>

                <!-- Map & FAQ -->
                <div>
                    <!-- Google Map embed -->
                    <div style="border-radius:20px;overflow:hidden;box-shadow:0 5px 25px rgba(93,78,55,.08);margin-bottom:25px;">
                        <iframe
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3919.4!2d106.7!3d10.77!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMTDCsDQ2JzEyLjAiTiAxMDbCsDQyJzAwLjAiRQ!5e0!3m2!1svi!2svn!4v1600000000000!5m2!1svi!2svn"
                            width="100%" height="250" style="border:0;display:block;" allowfullscreen loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade" title="Vị trí PetShop"></iframe>
                    </div>

                    <!-- FAQ -->
                    <div style="background:#fff;border-radius:20px;padding:28px;box-shadow:0 5px 25px rgba(93,78,55,.08);">
                        <h3 style="font-size:1.1rem;font-weight:700;color:#5D4E37;margin:0 0 18px;display:flex;align-items:center;gap:8px;">
                            <i class="bi bi-question-circle-fill" style="color:#EC802B;"></i> Câu hỏi thường gặp
                        </h3>
                        <?php
                        $faqs = array(
                            array('q'=>'Thời gian giao hàng bao lâu?','a'=>'Nội thành TP.HCM: 2–4 giờ. Tỉnh thành khác: 1–3 ngày làm việc.'),
                            array('q'=>'Chính sách đổi trả như thế nào?','a'=>'Đổi trả trong 7 ngày, sản phẩm còn nguyên seal, có hóa đơn mua hàng.'),
                            array('q'=>'Có thể đặt lịch dịch vụ online không?','a'=>'Có! Điền form hoặc gọi hotline để đặt lịch Spa, Grooming, Khám bệnh.'),
                        );
                        foreach ($faqs as $i => $faq): ?>
                        <div style="margin-bottom:<?php echo $i < count($faqs)-1 ? '14px' : '0'; ?>;padding-bottom:<?php echo $i < count($faqs)-1 ? '14px' : '0'; ?>;border-bottom:<?php echo $i < count($faqs)-1 ? '1px solid #F5EDE0' : 'none'; ?>;">
                            <div style="font-weight:700;color:#5D4E37;margin-bottom:5px;display:flex;align-items:flex-start;gap:8px;font-size:.92rem;">
                                <i class="bi bi-patch-question-fill" style="color:#EC802B;flex-shrink:0;margin-top:2px;"></i>
                                <?php echo $faq['q']; ?>
                            </div>
                            <p style="color:#7A6B5A;font-size:.88rem;line-height:1.6;margin:0 0 0 24px;"><?php echo $faq['a']; ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Kết nối nhanh -->
    <section style="background:#fff;padding:50px 0;border-top:1px solid #F5EDE0;">
        <div class="container">
            <div style="text-align:center;margin-bottom:36px;">
                <h2 style="font-size:1.8rem;font-weight:800;color:#5D4E37;margin:0 0 8px;">Kết nối với chúng tôi</h2>
                <p style="color:#7A6B5A;margin:0;">Chọn kênh phù hợp để liên hệ nhanh nhất</p>
            </div>
            <div style="display:grid;grid-template-columns:repeat(6,1fr);gap:14px;max-width:860px;margin:0 auto;">

                <?php
                $channels = array(
                    array('href'=>'tel:0123456789','bg'=>'#EC802B','icon'=>'bi-telephone-fill','label'=>'Gọi điện','sub'=>'0123 456 789'),
                    array('href'=>'https://zalo.me/0123456789','bg'=>'#0068FF','icon'=>'bi-chat-dots-fill','label'=>'Zalo','sub'=>'Chat ngay','target'=>'_blank'),
                    array('href'=>'mailto:info@petshop.com','bg'=>'#66BCB4','icon'=>'bi-envelope-fill','label'=>'Email','sub'=>'Gửi thư'),
                    array('href'=>'https://facebook.com/petshop','bg'=>'#1877F2','icon'=>'bi-facebook','label'=>'Facebook','sub'=>'Nhắn tin','target'=>'_blank'),
                    array('href'=>'https://instagram.com/petshop','bg'=>'#E1306C','icon'=>'bi-instagram','label'=>'Instagram','sub'=>'Follow','target'=>'_blank'),
                    array('href'=>'https://youtube.com/@petshop','bg'=>'#FF0000','icon'=>'bi-youtube','label'=>'YouTube','sub'=>'Subscribe','target'=>'_blank'),
                );
                foreach ($channels as $ch):
                    $target = isset($ch['target']) ? 'target="_blank" rel="noopener"' : '';
                ?>
                <a href="<?php echo $ch['href']; ?>" <?php echo $target; ?>
                   style="display:flex;flex-direction:column;align-items:center;gap:8px;padding:20px 10px;background:#FDF8F3;border-radius:16px;text-decoration:none;border:2px solid transparent;transition:all .22s;"
                   onmouseover="this.style.borderColor='<?php echo $ch['bg']; ?>';this.style.transform='translateY(-4px)';this.style.background='#fff';"
                   onmouseout="this.style.borderColor='transparent';this.style.transform='';this.style.background='#FDF8F3';">
                    <div style="width:48px;height:48px;background:<?php echo $ch['bg']; ?>;border-radius:14px;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 14px <?php echo $ch['bg']; ?>44;">
                        <i class="bi <?php echo $ch['icon']; ?>" style="font-size:1.4rem;color:#fff;"></i>
                    </div>
                    <span style="font-weight:700;color:#5D4E37;font-size:.82rem;"><?php echo $ch['label']; ?></span>
                    <span style="font-size:.75rem;color:<?php echo $ch['bg']; ?>;font-weight:600;"><?php echo $ch['sub']; ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

</main>

<style>
@media(max-width:992px){
    section div[style*="grid-template-columns: repeat(4"] { grid-template-columns: repeat(2,1fr) !important; }
    section div[style*="grid-template-columns: 1fr 1fr"] { grid-template-columns: 1fr !important; }
    section div[style*="grid-template-columns: repeat(6"] { grid-template-columns: repeat(3,1fr) !important; }
}
@media(max-width:576px){
    section div[style*="grid-template-columns: repeat(4"] { grid-template-columns: 1fr !important; }
    section div[style*="grid-template-columns: repeat(6"] { grid-template-columns: repeat(2,1fr) !important; }
    section div[style*="grid-template-columns: 1fr 1fr"] { grid-template-columns: 1fr !important; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form   = document.getElementById('contactForm');
    const btn    = document.getElementById('contactSubmitBtn');
    const msgBox = document.getElementById('contactMessage');

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-arrow-repeat" style="animation:spin .8s linear infinite;display:inline-block;"></i> Đang gửi...';

        const fd = new FormData();
        fd.append('action',  'petshop_contact_form');
        fd.append('nonce',   form.querySelector('[name="nonce"]').value);
        fd.append('name',    document.getElementById('contactName').value);
        fd.append('phone',   document.getElementById('contactPhone').value);
        fd.append('email',   document.getElementById('contactEmail').value);
        fd.append('subject', document.getElementById('contactSubject').value);
        fd.append('message', document.getElementById('contactMessageInput').value);

        try {
            const res  = await fetch('<?php echo admin_url("admin-ajax.php"); ?>', {method:'POST', body:fd});
            const data = await res.json();
            msgBox.style.display = 'block';
            if (data.success) {
                msgBox.innerHTML = '<div style="padding:16px 20px;background:linear-gradient(135deg,#d4edda,#c3e6cb);color:#155724;border-radius:12px;display:flex;align-items:center;gap:12px;"><i class="bi bi-check-circle-fill" style="font-size:1.3rem;"></i><div><strong>Gửi thành công!</strong><br><span style="font-size:.9rem;">' + data.data.message + '</span></div></div>';
                form.reset();
                btn.innerHTML = '<i class="bi bi-check-circle-fill"></i> Đã gửi';
                btn.style.background = '#28a745';
            } else {
                msgBox.innerHTML = '<div style="padding:16px 20px;background:#f8d7da;color:#721c24;border-radius:12px;display:flex;align-items:center;gap:12px;"><i class="bi bi-exclamation-circle-fill" style="font-size:1.3rem;"></i><span>' + (data.data?.message || 'Vui lòng thử lại') + '</span></div>';
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-send-fill"></i> Gửi tin nhắn';
            }
        } catch(e) {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-send-fill"></i> Gửi tin nhắn';
        }
    });
});
</script>

<?php get_footer(); ?>