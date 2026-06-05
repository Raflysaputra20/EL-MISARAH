<style>
    .dashboard-card {
        background: white;
        border-radius: 12px;
        border: 1px solid #f3f4f6;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
        padding: 24px;
        height: 100%;
        position: relative;
    }

    .stat-title {
        font-size: 14px;
        font-weight: 700;
        color: #374151;
        margin-bottom: 16px;
    }

    .stat-value {
        font-size: 36px;
        font-weight: 800;
        color: #1f2937;
        margin-bottom: 8px;
        line-height: 1;
    }

    .stat-subtitle {
        font-size: 11px;
        font-weight: 600;
        color: #1ab35d;
    }

    .stat-icon-wrapper {
        position: absolute;
        top: 24px;
        right: 24px;
        width: 48px;
        height: 48px;
        background-color: #e8f7f0;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #1ab35d;
    }

    /* Booking List */
    .booking-item {
        background-color: #f2fbf5;
        border: 1px solid #e1f5e8;
        border-radius: 12px;
        padding: 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 12px;
    }

    .booking-item:last-child {
        margin-bottom: 0;
    }

    .booking-info {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .booking-icon {
        color: #1ab35d;
    }

    .booking-details {
        display: flex;
        flex-direction: column;
    }

    .booking-name {
        font-weight: 700;
        font-size: 14px;
        color: #1f2937;
        margin-bottom: 2px;
    }

    .booking-room {
        font-size: 11px;
        color: #6b7280;
    }

    .badge-visit {
        background-color: transparent;
        color: #1ab35d;
        border: 1px solid #1ab35d;
        border-radius: 20px;
        padding: 4px 16px;
        font-size: 11px;
        font-weight: 600;
    }

    .badge-booking {
        background-color: transparent;
        color: #1ab35d;
        border: 1px solid #1ab35d;
        border-radius: 20px;
        padding: 4px 16px;
        font-size: 11px;
        font-weight: 600;
    }
</style>

<!-- TOP STATS ROW -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="dashboard-card">
            <div class="stat-title">Total Penghuni</div>
            <div class="stat-value">13</div>
            <div class="stat-subtitle">+2 bulan ini</div>
            <div class="stat-icon-wrapper">
                <i data-lucide="users" style="width:24px; height:24px;"></i>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="dashboard-card">
            <div class="stat-title">Total Kamar Tersedia</div>
            <div class="stat-value">3</div>
            <!-- No subtitle in design for this one -->
            <div class="stat-icon-wrapper">
                <i data-lucide="bed" style="width:24px; height:24px;"></i>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="dashboard-card">
            <div class="stat-title">Pengaduan Aktif</div>
            <div class="stat-value">5</div>
            <div class="stat-subtitle">Pengaduan Hari Ini</div>
            <div class="stat-icon-wrapper">
                <i data-lucide="message-square-warning" style="width:24px; height:24px;"></i>
            </div>
        </div>
    </div>
</div>

<!-- BOTTOM ROW -->
<div class="row g-4">
    
    <!-- DONUT CHART -->
    <div class="col-md-5">
        <div class="dashboard-card d-flex flex-column">
            <h5 class="fw-bold mb-4" style="font-size: 16px; color:#1f2937;">Status Kamar</h5>
            <div class="flex-grow-1 d-flex align-items-center justify-content-center position-relative pb-4">
                <canvas id="roomStatusChart" style="max-height: 250px; max-width: 250px;"></canvas>
            </div>
            
            <!-- Custom Legend -->
            <div class="d-flex justify-content-center gap-4 mt-auto">
                <div class="d-flex align-items-center gap-2">
                    <span style="width:12px; height:12px; border-radius:50%; background-color:#1ab35d;"></span>
                    <span style="font-size:11px; color:#6b7280;">Status Kamar</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span style="width:12px; height:12px; border-radius:50%; background-color:#f59e0b;"></span>
                    <span style="font-size:11px; color:#6b7280;">Maintenance</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span style="width:12px; height:12px; border-radius:50%; background-color:#e5e7eb;"></span>
                    <span style="font-size:11px; color:#6b7280;">Kamar Kosong</span>
                </div>
            </div>
        </div>
    </div>

    <!-- BOOKING LIST -->
    <div class="col-md-7">
        <div class="dashboard-card">
            <h5 class="fw-bold mb-4" style="font-size: 16px; color:#1f2937;">Booking dan Visit</h5>
            
            <div class="booking-list">
                
                <div class="booking-item">
                    <div class="booking-info">
                        <i data-lucide="calendar-check-2" class="booking-icon" style="width:24px; height:24px;"></i>
                        <div class="booking-details">
                            <span class="booking-name">Bang Alek</span>
                            <span class="booking-room">Kamar 15, 20 April 2026</span>
                        </div>
                    </div>
                    <span class="badge-visit">Visit</span>
                </div>

                <div class="booking-item">
                    <div class="booking-info">
                        <i data-lucide="calendar-check-2" class="booking-icon" style="width:24px; height:24px;"></i>
                        <div class="booking-details">
                            <span class="booking-name">Kholik mbojo</span>
                            <span class="booking-room">Kamar 08, 22 April 2026</span>
                        </div>
                    </div>
                    <span class="badge-booking">Booking</span>
                </div>

                <div class="booking-item">
                    <div class="booking-info">
                        <i data-lucide="calendar-check-2" class="booking-icon" style="width:24px; height:24px;"></i>
                        <div class="booking-details">
                            <span class="booking-name">Abdul Deadline</span>
                            <span class="booking-room">Kamar 15, 20 April 2026</span>
                        </div>
                    </div>
                    <span class="badge-visit">Visit</span>
                </div>

                <div class="booking-item">
                    <div class="booking-info">
                        <i data-lucide="calendar-check-2" class="booking-icon" style="width:24px; height:24px;"></i>
                        <div class="booking-details">
                            <span class="booking-name">Almi Surti</span>
                            <span class="booking-room">Kamar 15, 20 April 2026</span>
                        </div>
                    </div>
                    <span class="badge-visit">Visit</span>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Wait for Chart.js to load via CDN in admin_layout.php
    const ctx = document.getElementById('roomStatusChart').getContext('2d');
    
    // Data roughly matching the visual proportion in the design
    // Green (Occupied) ~70%, Yellow (Maintenance) ~15%, Gray (Empty) ~15%
    const data = {
        datasets: [{
            data: [13, 2, 3], 
            backgroundColor: [
                '#1ab35d', // Green
                '#f59e0b', // Yellow/Orange
                '#e5e7eb'  // Light Gray
            ],
            borderWidth: 5,
            borderColor: '#ffffff', // White space between segments
            cutout: '65%' // Donut hole size
        }]
    };

    new Chart(ctx, {
        type: 'doughnut',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false // We use our custom HTML legend
                },
                tooltip: {
                    enabled: false
                }
            },
            animation: {
                animateScale: true,
                animateRotate: true
            }
        }
    });
});
</script>
