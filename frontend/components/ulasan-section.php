<?php
require_once __DIR__ . '/../../backend/config/database.php';

try {
    $stmtUlasan = $conn->query("
        SELECT u.rating, u.komentar, u.created_at, usr.nama, usr.foto
        FROM ulasan u
        JOIN users usr ON u.user_id = usr.id
        WHERE u.tampilkan = 1
        ORDER BY u.created_at DESC
        LIMIT 6
    ");
    $ulasans = $stmtUlasan->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $ulasans = [];
}
?>

<?php if (!empty($ulasans)): ?>
<section id="ulasan" class="py-5" style="background-color: #f8f9fa;">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold" style="color: #1f2937;">Ulasan Penghuni</h2>
            <p class="text-muted">Apa kata mereka tentang Kost Elmi Sarah</p>
        </div>
        
        <div class="row g-4 justify-content-center">
            <?php foreach ($ulasans as $ulasan): ?>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm" style="border-radius: 16px;">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-shrink-0">
                                <?php if (!empty($ulasan['foto']) && file_exists(__DIR__ . '/../../uploads/profil/' . $ulasan['foto'])): ?>
                                    <img src="uploads/profil/<?= htmlspecialchars($ulasan['foto']) ?>" alt="Profil" class="rounded-circle object-fit-cover" width="50" height="50">
                                <?php else: ?>
                                    <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white" style="width: 50px; height: 50px; background: linear-gradient(135deg, #11a654, #0d8e47); font-size: 20px;">
                                        <?= strtoupper(substr($ulasan['nama'], 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-0 fw-bold"><?= htmlspecialchars($ulasan['nama']) ?></h6>
                                <small class="text-muted"><?= date('d M Y', strtotime($ulasan['created_at'])) ?></small>
                            </div>
                        </div>
                        
                        <div class="mb-2">
                            <?php 
                            $rating = (int)$ulasan['rating'];
                            for ($i = 1; $i <= 5; $i++): 
                                if ($i <= $rating):
                            ?>
                                <i data-lucide="star" style="width: 16px; height: 16px; color: #f59e0b; fill: #f59e0b;"></i>
                            <?php else: ?>
                                <i data-lucide="star" style="width: 16px; height: 16px; color: #d1d5db;"></i>
                            <?php 
                                endif;
                            endfor; 
                            ?>
                        </div>
                        
                        <p class="card-text text-muted mb-0" style="font-size: 14px; font-style: italic;">
                            "<?= htmlspecialchars($ulasan['komentar']) ?>"
                        </p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
