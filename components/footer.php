<?php
// components/footer.php
?>
            </div> </div> </main>

    <script>
        // Theme Toggle Logic
        const htmlElement = document.documentElement;
        if (localStorage.getItem('theme') === 'dark') {
            htmlElement.classList.add('dark');
        } else {
            htmlElement.classList.remove('dark');
            localStorage.setItem('theme', 'light'); 
        }

        const themeBtn = document.getElementById('theme-toggle');
        if(themeBtn) {
            themeBtn.addEventListener('click', () => {
                htmlElement.classList.toggle('dark');
                if (htmlElement.classList.contains('dark')) {
                    localStorage.setItem('theme', 'dark');
                } else {
                    localStorage.setItem('theme', 'light');
                }
            });
        }

        // Global Modal Logic
        function toggleModal(modalID) {
            const modal = document.getElementById(modalID);
            if(modal) {
                modal.classList.toggle('active');
            }
        }
    </script>
</body>
</html>