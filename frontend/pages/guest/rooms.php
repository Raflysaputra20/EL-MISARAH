<div class="container mt-4">
    <h2>Daftar Kamar</h2>

    <div class="row">
        <?php foreach ($kamar as $room): ?>
            <?php include __DIR__ . '/../../components/room-card.php'; ?>
        <?php endforeach; ?>
    </div>
</div>