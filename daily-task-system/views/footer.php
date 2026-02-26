        </main>
    </div> <!-- row -->
</div> <!-- container-fluid -->

<footer class="footer-custom text-center py-3 mt-4">
    <div class="container">
        <small>&copy; <?= date('Y') ?> <span class="fw-bold">Daily Task System</span>. All rights reserved.</small>
    </div>
</footer>

<style>
    .footer-custom {
        background: #f8f9fa; /* light gray background */
        color: #000; /* black text */
        border-top: 5px solid transparent;
        border-image: linear-gradient(90deg, #000000, #8B0000);
        border-image-slice: 1;
        font-size: 0.9rem;
    }
    .footer-custom small {
        color: #555; /* medium gray text */
    }
    .footer-custom small span {
        color: #8B0000; /* company red for brand name */
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
