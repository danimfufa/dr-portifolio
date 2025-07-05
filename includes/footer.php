<div class="container mt-4">
    ```

Create `dr_portfolio/includes/footer.php`:

```php
<?php
// includes/footer.php
?>
    </div> <footer class="bg-light text-center text-lg-start mt-5 py-3">
        <div class="container">
            <p>&copy; <?php echo date("Y"); ?> Dr. [Your Name]. All rights reserved.</p>
        </div>
    </footer>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="/dr_portfolio/js/script.js"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="js/script.js"></script>
</body>
</html>
<?php
// Close database connection at the very end of the request
if (isset($conn)) {
    $conn->close();
}
?>