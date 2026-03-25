<?php
/**
 * Template Name: Trang Giới Thiệu
 * @package PetShop
 */
get_header(); ?>

<!-- Page Header -->
<section class="page-header" style="background:#FDF8F3;padding:60px 0;text-align:center;border-bottom:2px solid #F5EDE0;">
    <div class="container">
        <h1 class="page-title" style="font-size:2.5rem;font-weight:800;margin:0 0 10px;color:#5D4E37;display:flex;align-items:center;justify-content:center;gap:12px;">
            <i class="bi bi-info-circle-fill" style="color:#EC802B;"></i> Giới Thiệu
        </h1>
        <p style="font-size:1.1rem;color:#7A6B5A;margin:0;">Chào mừng bạn đến với PetShop — Nơi yêu thương thú cưng</p>
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
            <span style="color:#EC802B;font-weight:600;">Giới thiệu</span>
        </nav>
    </div>
</section>

<main class="main-content" style="background:#FDF8F3;">

    <!-- Hero Section -->
    <section style="padding:80px 0;background:#fff;">
        <div class="container">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:60px;align-items:center;">
                <div>
                    <span style="display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#EC802B,#F5994D);color:#fff;padding:8px 20px;border-radius:30px;font-size:.9rem;font-weight:700;margin-bottom:22px;">
                        <i class="bi bi-heart-fill"></i> Về chúng tôi
                    </span>
                    <h2 style="font-size:2.4rem;font-weight:800;color:#5D4E37;margin:0 0 20px;line-height:1.3;">
                        Tình yêu với thú cưng<br>
                        <span style="color:#EC802B;">là động lực của chúng tôi</span>
                    </h2>
                    <p style="font-size:1.05rem;color:#7A6B5A;line-height:1.8;margin-bottom:18px;">
                        <strong>PetShop</strong> được thành lập với sứ mệnh mang đến những sản phẩm và dịch vụ tốt nhất cho thú cưng. Chúng tôi hiểu rằng thú cưng không chỉ là vật nuôi, mà là thành viên trong gia đình.
                    </p>
                    <p style="font-size:.95rem;color:#7A6B5A;line-height:1.8;margin-bottom:32px;">
                        Với hơn <strong>10 năm kinh nghiệm</strong>, đội ngũ chuyên gia luôn sẵn sàng tư vấn và hỗ trợ bạn chăm sóc thú cưng yêu quý.
                    </p>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                        <?php
                        $features = array(
                            array('icon'=>'bi-patch-check-fill','text'=>'Sản phẩm chính hãng'),
                            array('icon'=>'bi-people-fill','text'=>'Đội ngũ chuyên nghiệp'),
                            array('icon'=>'bi-tags-fill','text'=>'Giá cả hợp lý'),
                            array('icon'=>'bi-headset','text'=>'Hỗ trợ 24/7'),
                        );
                        foreach ($features as $f): ?>
                        <div style="display:flex;align-items:center;gap:12px;background:#FDF8F3;padding:14px 16px;border-radius:12px;">
                            <i class="bi <?php echo $f['icon']; ?>" style="font-size:1.3rem;color:#EC802B;flex-shrink:0;"></i>
                            <span style="font-weight:600;color:#5D4E37;font-size:.92rem;"><?php echo $f['text']; ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div style="position:relative;">
                    <img src="https://images.unsplash.com/photo-1601758228041-f3b2795255f1?w=600" alt="PetShop"
                         style="width:100%;border-radius:28px;box-shadow:0 20px 60px rgba(93,78,55,.18);">
                    <div style="position:absolute;bottom:-28px;left:-28px;background:#fff;padding:22px 30px;border-radius:18px;box-shadow:0 10px 40px rgba(93,78,55,.12);border-left:4px solid #EC802B;">
                        <div style="font-size:2.2rem;font-weight:800;color:#EC802B;line-height:1;">10+</div>
                        <div style="font-weight:600;color:#5D4E37;font-size:.9rem;">Năm kinh nghiệm</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats -->
    <section style="background:linear-gradient(135deg,#EC802B 0%,#F5994D 100%);padding:60px 0;">
        <div class="container">
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:30px;text-align:center;color:#fff;">
                <?php
                $stats = array(
                    array('icon'=>'bi-box-seam-fill','num'=>'10K+','label'=>'Sản phẩm'),
                    array('icon'=>'bi-people-fill','num'=>'5K+','label'=>'Khách hàng'),
                    array('icon'=>'bi-star-fill','num'=>'4.9','label'=>'Đánh giá TB'),
                    array('icon'=>'bi-award-fill','num'=>'10+','label'=>'Năm kinh nghiệm'),
                );
                foreach ($stats as $s): ?>
                <div>
                    <i class="bi <?php echo $s['icon']; ?>" style="font-size:2.2rem;opacity:.9;display:block;margin-bottom:10px;"></i>
                    <div style="font-size:2.8rem;font-weight:800;line-height:1;margin-bottom:8px;"><?php echo $s['num']; ?></div>
                    <div style="opacity:.9;font-size:.95rem;"><?php echo $s['label']; ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Giá trị cốt lõi -->
    <section style="padding:80px 0;background:#fff;">
        <div class="container">
            <div style="text-align:center;margin-bottom:50px;">
                <span style="display:inline-flex;align-items:center;gap:8px;background:#FDF8F3;color:#EC802B;padding:8px 20px;border-radius:30px;font-size:.9rem;font-weight:700;border:1.5px solid #E8CCAD;margin-bottom:16px;">
                    <i class="bi bi-gem"></i> Giá trị cốt lõi
                </span>
                <h2 style="font-size:2rem;font-weight:800;color:#5D4E37;margin:0;">Tại sao chọn chúng tôi?</h2>
            </div>
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:24px;">
                <?php
                $values = array(
                    array('icon'=>'bi-shield-fill-check','title'=>'Chất lượng đảm bảo','desc'=>'100% sản phẩm chính hãng, có giấy tờ kiểm định rõ ràng. Cam kết hoàn tiền nếu không đúng chất lượng.'),
                    array('icon'=>'bi-lightbulb-fill','title'=>'Tư vấn chuyên sâu','desc'=>'Đội ngũ chuyên gia thú cưng tư vấn tận tình, giúp bạn chọn sản phẩm phù hợp nhất.'),
                    array('icon'=>'bi-truck-front-fill','title'=>'Giao hàng nhanh','desc'=>'Giao hàng 2 giờ nội thành TP.HCM. Đóng gói an toàn, bảo quản đúng tiêu chuẩn cho thú cưng.'),
                    array('icon'=>'bi-arrow-repeat','title'=>'Đổi trả dễ dàng','desc'=>'Chính sách đổi trả 7 ngày linh hoạt. Hỗ trợ 24/7 qua hotline và chat trực tuyến.'),
                    array('icon'=>'bi-wallet2','title'=>'Giá cả minh bạch','desc'=>'Niêm yết giá rõ ràng, thường xuyên có chương trình ưu đãi và tích điểm thành viên hấp dẫn.'),
                    array('icon'=>'bi-heart-fill','title'=>'Cộng đồng yêu thú cưng','desc'=>'Kết nối hàng nghìn pet lovers, chia sẻ kinh nghiệm chăm sóc thú cưng qua các kênh mạng xã hội.'),
                );
                foreach ($values as $v): ?>
                <div style="background:#FDF8F3;border-radius:20px;padding:28px;border:2px solid transparent;transition:all .25s;"
                     onmouseover="this.style.borderColor='#EC802B';this.style.background='#fff';this.style.transform='translateY(-5px)';"
                     onmouseout="this.style.borderColor='transparent';this.style.background='#FDF8F3';this.style.transform='';">
                    <div style="width:56px;height:56px;background:linear-gradient(135deg,#EC802B,#F5994D);border-radius:16px;display:flex;align-items:center;justify-content:center;margin-bottom:18px;box-shadow:0 4px 14px rgba(236,128,43,.3);">
                        <i class="bi <?php echo $v['icon']; ?>" style="font-size:1.5rem;color:#fff;"></i>
                    </div>
                    <h3 style="font-size:1.05rem;font-weight:700;color:#5D4E37;margin:0 0 10px;"><?php echo $v['title']; ?></h3>
                    <p style="color:#7A6B5A;font-size:.9rem;line-height:1.7;margin:0;"><?php echo $v['desc']; ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Team -->
    <section style="padding:80px 0;background:#FDF8F3;">
        <div class="container">
            <div style="text-align:center;margin-bottom:50px;">
                <span style="display:inline-flex;align-items:center;gap:8px;background:#fff;color:#EC802B;padding:8px 20px;border-radius:30px;font-size:.9rem;font-weight:700;border:1.5px solid #E8CCAD;margin-bottom:16px;">
                    <i class="bi bi-people-fill"></i> Đội ngũ
                </span>
                <h2 style="font-size:2rem;font-weight:800;color:#5D4E37;margin:0;">Những người đam mê thú cưng</h2>
            </div>
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:20px;">
                <?php
                $team = array(
                    array('name'=>'Nguyễn Văn A','role'=>'Giám đốc & Bác sĩ thú y','avatar'=>'https://images.unsplash.com/photo-1560250097-0b93528c311a?w=200','icon'=>'bi-star-fill'),
                    array('name'=>'Trần Thị B','role'=>'Chuyên gia Grooming','avatar'=>'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=200','icon'=>'bi-scissors'),
                    array('name'=>'Lê Văn C','role'=>'Chuyên gia dinh dưỡng','avatar'=>'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=200','icon'=>'bi-heart-pulse-fill'),
                    array('name'=>'Phạm Thị D','role'=>'Chăm sóc khách hàng','avatar'=>'https://images.unsplash.com/photo-1580489944761-15a19d654956?w=200','icon'=>'bi-headset'),
                );
                foreach ($team as $member): ?>
                <div style="background:#fff;border-radius:20px;padding:24px 20px;text-align:center;box-shadow:0 5px 20px rgba(93,78,55,.07);transition:all .25s;"
                     onmouseover="this.style.transform='translateY(-5px)';this.style.boxShadow='0 15px 40px rgba(236,128,43,.15)';"
                     onmouseout="this.style.transform='';this.style.boxShadow='0 5px 20px rgba(93,78,55,.07)';">
                    <div style="position:relative;display:inline-block;margin-bottom:14px;">
                        <img src="<?php echo $member['avatar']; ?>&auto=format&fit=crop&w=120&h=120" alt="<?php echo $member['name']; ?>"
                             style="width:90px;height:90px;border-radius:50%;object-fit:cover;border:3px solid #F5EDE0;">
                        <span style="position:absolute;bottom:0;right:0;width:28px;height:28px;background:linear-gradient(135deg,#EC802B,#F5994D);border-radius:50%;display:flex;align-items:center;justify-content:center;border:2px solid #fff;">
                            <i class="bi <?php echo $member['icon']; ?>" style="font-size:.75rem;color:#fff;"></i>
                        </span>
                    </div>
                    <h4 style="font-size:.95rem;font-weight:700;color:#5D4E37;margin:0 0 5px;"><?php echo $member['name']; ?></h4>
                    <p style="font-size:.82rem;color:#EC802B;font-weight:600;margin:0;"><?php echo $member['role']; ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section style="background:linear-gradient(135deg,#EC802B 0%,#F5994D 100%);padding:60px 0;text-align:center;">
        <div class="container">
            <h2 style="color:#fff;font-size:2rem;font-weight:800;margin:0 0 12px;">
                <i class="bi bi-gift-fill"></i> Sẵn sàng trải nghiệm?
            </h2>
            <p style="color:rgba(255,255,255,.9);font-size:1.05rem;margin:0 0 28px;">Khám phá hàng nghìn sản phẩm và dịch vụ chất lượng cho thú cưng của bạn</p>
            <div style="display:flex;gap:16px;justify-content:center;flex-wrap:wrap;">
                <a href="<?php echo home_url('/san-pham/'); ?>"
                   style="display:inline-flex;align-items:center;gap:8px;padding:14px 32px;background:#fff;color:#EC802B;text-decoration:none;border-radius:50px;font-weight:700;font-size:1rem;transition:all .2s;"
                   onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 6px 20px rgba(0,0,0,.15)';"
                   onmouseout="this.style.transform='';this.style.boxShadow='';">
                    <i class="bi bi-bag-fill"></i> Mua sắm ngay
                </a>
                <a href="<?php echo home_url('/lien-he/'); ?>"
                   style="display:inline-flex;align-items:center;gap:8px;padding:14px 32px;background:transparent;color:#fff;text-decoration:none;border-radius:50px;font-weight:700;font-size:1rem;border:2px solid rgba(255,255,255,.7);transition:all .2s;"
                   onmouseover="this.style.borderColor='#fff';this.style.background='rgba(255,255,255,.1)';"
                   onmouseout="this.style.borderColor='rgba(255,255,255,.7)';this.style.background='transparent';">
                    <i class="bi bi-telephone-fill"></i> Liên hệ ngay
                </a>
            </div>
        </div>
    </section>

</main>

<style>
@media(max-width:992px){
    section div[style*="grid-template-columns: 1fr 1fr"] { grid-template-columns:1fr !important; }
    section div[style*="grid-template-columns: repeat(4"] { grid-template-columns:repeat(2,1fr) !important; }
    section div[style*="grid-template-columns: repeat(3"] { grid-template-columns:repeat(2,1fr) !important; }
}
@media(max-width:576px){
    section div[style*="grid-template-columns: repeat(4"] { grid-template-columns:1fr !important; }
    section div[style*="grid-template-columns: repeat(3"] { grid-template-columns:1fr !important; }
}
</style>

<?php get_footer(); ?>