<?php
/**
 * Template Name: Trang Dịch Vụ
 * 
 * @package PetShop
 */
get_header(); ?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1><i class="bi bi-stars"></i> Dịch Vụ Của Chúng Tôi</h1>
        <p>Chăm sóc toàn diện cho thú cưng yêu quý của bạn</p>
    </div>
</div>

<section class="services-section" style="padding: 60px 0;">
    <div class="container">
        <?php petshop_breadcrumb(); ?>
        
        <!-- Services Intro -->
        <div class="services-intro" style="text-align: center; max-width: 700px; margin: 30px auto 50px;">
            <h2>Dịch vụ chuyên nghiệp <span style="color: #EC802B;">5 sao</span></h2>
            <p style="color: #7A6B5A; line-height: 1.8;">Với đội ngũ chuyên gia giàu kinh nghiệm và trang thiết bị hiện đại, chúng tôi cam kết mang đến những dịch vụ chăm sóc tốt nhất cho thú cưng của bạn.</p>
        </div>
        
        <!-- Main Services -->
        <div class="services-grid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px;">
            
            <!-- Service 1: Grooming -->
            <div class="service-card" style="background: #fff; border-radius: 25px; overflow: hidden; box-shadow: 0 10px 40px rgba(93, 78, 55, 0.1); transition: all 0.3s;">
                <div class="service-image" style="height: 200px; overflow: hidden;">
                    <img src="https://images.unsplash.com/photo-1516734212186-a967f81ad0d7?w=500" alt="Spa & Grooming" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
                <div class="service-content" style="padding: 30px;">
                    <div class="service-icon" style="width: 70px; height: 70px; background: linear-gradient(135deg, #EC802B 0%, #F5994D 100%); border-radius: 20px; display: flex; align-items: center; justify-content: center; margin: -65px auto 20px; color: #fff; font-size: 1.8rem; box-shadow: 0 10px 25px rgba(236, 128, 43, 0.3);">
                        <i class="bi bi-scissors"></i>
                    </div>
                    <h3 style="text-align: center; margin-bottom: 15px;">Spa & Grooming</h3>
                    <p style="color: #7A6B5A; text-align: center; line-height: 1.7; margin-bottom: 20px;">Tắm, cắt tỉa lông, vệ sinh tai, cắt móng và làm đẹp toàn diện cho thú cưng.</p>
                    <ul style="color: #5D4E37; margin-bottom: 25px;">
                        <li style="padding: 8px 0; border-bottom: 1px solid #F5F5F5;"><i class="bi bi-check-circle-fill" style="color: #66BCB4; margin-right: 10px;"></i> Tắm & Sấy khô</li>
                        <li style="padding: 8px 0; border-bottom: 1px solid #F5F5F5;"><i class="bi bi-check-circle-fill" style="color: #66BCB4; margin-right: 10px;"></i> Cắt tỉa tạo kiểu</li>
                        <li style="padding: 8px 0; border-bottom: 1px solid #F5F5F5;"><i class="bi bi-check-circle-fill" style="color: #66BCB4; margin-right: 10px;"></i> Vệ sinh tai & mắt</li>
                        <li style="padding: 8px 0;"><i class="bi bi-check-circle-fill" style="color: #66BCB4; margin-right: 10px;"></i> Cắt móng & mài móng</li>
                    </ul>
                    <div style="text-align: center;">
                        <span style="display: block; font-size: 0.9rem; color: #7A6B5A; margin-bottom: 5px;">Giá từ</span>
                        <span style="font-size: 1.8rem; font-weight: 700; color: #EC802B;">150.000đ</span>
                    </div>
                    <a href="<?php echo home_url('/lien-he/'); ?>" class="btn btn-primary" style="width: 100%; margin-top: 20px;">
                        <i class="bi bi-calendar-check"></i> Đặt lịch ngay
                    </a>
                </div>
            </div>
            
            <!-- Service 2: Veterinary -->
            <div class="service-card" style="background: #fff; border-radius: 25px; overflow: hidden; box-shadow: 0 10px 40px rgba(93, 78, 55, 0.1); transition: all 0.3s; transform: scale(1.02);">
                <div class="service-badge" style="position: absolute; top: 20px; right: 20px; background: #EC802B; color: #fff; padding: 5px 15px; border-radius: 50px; font-size: 0.85rem; font-weight: 600;">Phổ biến</div>
                <div class="service-image" style="height: 200px; overflow: hidden; position: relative;">
                    <img src="https://images.unsplash.com/photo-1628009368231-7bb7cfcb0def?w=500" alt="Khám & Điều trị" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
                <div class="service-content" style="padding: 30px;">
                    <div class="service-icon" style="width: 70px; height: 70px; background: linear-gradient(135deg, #66BCB4 0%, #7ECEC6 100%); border-radius: 20px; display: flex; align-items: center; justify-content: center; margin: -65px auto 20px; color: #fff; font-size: 1.8rem; box-shadow: 0 10px 25px rgba(102, 188, 180, 0.3);">
                        <i class="bi bi-heart-pulse"></i>
                    </div>
                    <h3 style="text-align: center; margin-bottom: 15px;">Khám & Điều Trị</h3>
                    <p style="color: #7A6B5A; text-align: center; line-height: 1.7; margin-bottom: 20px;">Dịch vụ khám sức khỏe định kỳ, chẩn đoán và điều trị bệnh cho thú cưng.</p>
                    <ul style="color: #5D4E37; margin-bottom: 25px;">
                        <li style="padding: 8px 0; border-bottom: 1px solid #F5F5F5;"><i class="bi bi-check-circle-fill" style="color: #66BCB4; margin-right: 10px;"></i> Khám tổng quát</li>
                        <li style="padding: 8px 0; border-bottom: 1px solid #F5F5F5;"><i class="bi bi-check-circle-fill" style="color: #66BCB4; margin-right: 10px;"></i> Xét nghiệm máu</li>
                        <li style="padding: 8px 0; border-bottom: 1px solid #F5F5F5;"><i class="bi bi-check-circle-fill" style="color: #66BCB4; margin-right: 10px;"></i> Siêu âm & X-quang</li>
                        <li style="padding: 8px 0;"><i class="bi bi-check-circle-fill" style="color: #66BCB4; margin-right: 10px;"></i> Tiêm phòng đầy đủ</li>
                    </ul>
                    <div style="text-align: center;">
                        <span style="display: block; font-size: 0.9rem; color: #7A6B5A; margin-bottom: 5px;">Giá từ</span>
                        <span style="font-size: 1.8rem; font-weight: 700; color: #EC802B;">200.000đ</span>
                    </div>
                    <a href="<?php echo home_url('/lien-he/'); ?>" class="btn btn-primary" style="width: 100%; margin-top: 20px;">
                        <i class="bi bi-calendar-check"></i> Đặt lịch ngay
                    </a>
                </div>
            </div>
            
            <!-- Service 3: Hotel -->
            <div class="service-card" style="background: #fff; border-radius: 25px; overflow: hidden; box-shadow: 0 10px 40px rgba(93, 78, 55, 0.1); transition: all 0.3s;">
                <div class="service-image" style="height: 200px; overflow: hidden;">
                    <img src="https://images.unsplash.com/photo-1548199973-03cce0bbc87b?w=500" alt="Khách sạn thú cưng" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
                <div class="service-content" style="padding: 30px;">
                    <div class="service-icon" style="width: 70px; height: 70px; background: linear-gradient(135deg, #EDC55B 0%, #F5D47B 100%); border-radius: 20px; display: flex; align-items: center; justify-content: center; margin: -65px auto 20px; color: #fff; font-size: 1.8rem; box-shadow: 0 10px 25px rgba(237, 197, 91, 0.3);">
                        <i class="bi bi-house-heart"></i>
                    </div>
                    <h3 style="text-align: center; margin-bottom: 15px;">Khách Sạn Thú Cưng</h3>
                    <p style="color: #7A6B5A; text-align: center; line-height: 1.7; margin-bottom: 20px;">Dịch vụ trông giữ thú cưng khi bạn đi công tác hoặc du lịch.</p>
                    <ul style="color: #5D4E37; margin-bottom: 25px;">
                        <li style="padding: 8px 0; border-bottom: 1px solid #F5F5F5;"><i class="bi bi-check-circle-fill" style="color: #66BCB4; margin-right: 10px;"></i> Phòng riêng tiện nghi</li>
                        <li style="padding: 8px 0; border-bottom: 1px solid #F5F5F5;"><i class="bi bi-check-circle-fill" style="color: #66BCB4; margin-right: 10px;"></i> Camera theo dõi 24/7</li>
                        <li style="padding: 8px 0; border-bottom: 1px solid #F5F5F5;"><i class="bi bi-check-circle-fill" style="color: #66BCB4; margin-right: 10px;"></i> Cho ăn theo giờ</li>
                        <li style="padding: 8px 0;"><i class="bi bi-check-circle-fill" style="color: #66BCB4; margin-right: 10px;"></i> Dắt đi dạo hàng ngày</li>
                    </ul>
                    <div style="text-align: center;">
                        <span style="display: block; font-size: 0.9rem; color: #7A6B5A; margin-bottom: 5px;">Giá từ</span>
                        <span style="font-size: 1.8rem; font-weight: 700; color: #EC802B;">250.000đ/ngày</span>
                    </div>
                    <a href="<?php echo home_url('/lien-he/'); ?>" class="btn btn-primary" style="width: 100%; margin-top: 20px;">
                        <i class="bi bi-calendar-check"></i> Đặt lịch ngay
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Additional Services -->
        <div class="additional-services" style="margin-top: 60px;">
            <h2 style="text-align: center; margin-bottom: 40px;"><i class="bi bi-plus-circle"></i> Dịch Vụ Khác</h2>
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 25px;">
                
                <div class="mini-service" style="background: #fff; padding: 30px; border-radius: 20px; text-align: center; box-shadow: 0 5px 20px rgba(93, 78, 55, 0.08); transition: all 0.3s;">
                    <div style="width: 60px; height: 60px; background: #FDF8F3; border-radius: 50%; margin: 0 auto 15px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: #EC802B;">
                        <i class="bi bi-truck"></i>
                    </div>
                    <h4 style="margin-bottom: 10px;">Đưa đón tận nơi</h4>
                    <p style="color: #7A6B5A; font-size: 0.9rem; margin-bottom: 15px;">Miễn phí trong bán kính 5km</p>
                    <span style="color: #EC802B; font-weight: 700;">Miễn phí</span>
                </div>
                
                <div class="mini-service" style="background: #fff; padding: 30px; border-radius: 20px; text-align: center; box-shadow: 0 5px 20px rgba(93, 78, 55, 0.08); transition: all 0.3s;">
                    <div style="width: 60px; height: 60px; background: #FDF8F3; border-radius: 50%; margin: 0 auto 15px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: #66BCB4;">
                        <i class="bi bi-camera-video"></i>
                    </div>
                    <h4 style="margin-bottom: 10px;">Tư vấn Online</h4>
                    <p style="color: #7A6B5A; font-size: 0.9rem; margin-bottom: 15px;">Tư vấn qua video call</p>
                    <span style="color: #EC802B; font-weight: 700;">100.000đ/lần</span>
                </div>
                
                <div class="mini-service" style="background: #fff; padding: 30px; border-radius: 20px; text-align: center; box-shadow: 0 5px 20px rgba(93, 78, 55, 0.08); transition: all 0.3s;">
                    <div style="width: 60px; height: 60px; background: #FDF8F3; border-radius: 50%; margin: 0 auto 15px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: #EDC55B;">
                        <i class="bi bi-mortarboard"></i>
                    </div>
                    <h4 style="margin-bottom: 10px;">Huấn luyện</h4>
                    <p style="color: #7A6B5A; font-size: 0.9rem; margin-bottom: 15px;">Dạy các kỹ năng cơ bản</p>
                    <span style="color: #EC802B; font-weight: 700;">500.000đ/khóa</span>
                </div>
                
                <div class="mini-service" style="background: #fff; padding: 30px; border-radius: 20px; text-align: center; box-shadow: 0 5px 20px rgba(93, 78, 55, 0.08); transition: all 0.3s;">
                    <div style="width: 60px; height: 60px; background: #FDF8F3; border-radius: 50%; margin: 0 auto 15px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: #5D4E37;">
                        <i class="bi bi-balloon-heart"></i>
                    </div>
                    <h4 style="margin-bottom: 10px;">Sinh nhật Pet</h4>
                    <p style="color: #7A6B5A; font-size: 0.9rem; margin-bottom: 15px;">Tổ chức party cho pet</p>
                    <span style="color: #EC802B; font-weight: 700;">300.000đ</span>
                </div>
            </div>
        </div>
        
        <!-- CTA Section -->
        <div class="service-cta" style="margin-top: 60px; background: linear-gradient(135deg, #EC802B 0%, #F5994D 100%); padding: 50px; border-radius: 30px; text-align: center; color: #fff;">
            <h2 style="color: #fff; margin-bottom: 15px;"><i class="bi bi-gift"></i> Ưu đãi đặc biệt</h2>
            <p style="font-size: 1.1rem; opacity: 0.95; margin-bottom: 25px;">Giảm ngay 20% khi đặt combo 3 dịch vụ trở lên!</p>
            <a href="<?php echo home_url('/lien-he/'); ?>" class="btn btn-outline" style="background: #fff; color: #EC802B; border-color: #fff;">
                <i class="bi bi-telephone"></i> Liên hệ ngay: 0123 456 789
            </a>
        </div>
    </div>
</section>

<style>
.service-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 60px rgba(93, 78, 55, 0.15);
}
.mini-service:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px rgba(93, 78, 55, 0.12);
}
@media (max-width: 992px) {
    .services-grid {
        grid-template-columns: 1fr !important;
    }
    .additional-services > div:last-child {
        grid-template-columns: repeat(2, 1fr) !important;
    }
}
@media (max-width: 576px) {
    .additional-services > div:last-child {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php get_footer(); ?>
