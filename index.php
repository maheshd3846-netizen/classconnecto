<?php
session_start();
if(isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClassConnecto - Login</title>
    <!-- Tailwind CSS (CDN) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css?v=4">
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Three.js Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
</head>
<body class="flex items-center justify-center min-h-screen transition-colors duration-300 relative overflow-hidden bg-gray-50 dark:bg-slate-900">
    <div id="bg-canvas" class="absolute inset-0 z-0 pointer-events-none"></div>
    <?php include 'components/background.php'; ?>
    
    <!-- Perspective Wrapper -->
    <div class="relative z-10 w-full max-w-lg h-[550px]" style="perspective: 1500px;">
        
        <!-- 3D Card Container -->
        <div id="login-card" class="relative w-full h-full transition-transform duration-1000" style="transform-style: preserve-3d;">
            
            <!-- FRONT SIDE: Student Login -->
            <div id="front" class="absolute w-full h-full backface-hidden glass p-8 md:p-12 rounded-[2.5rem] shadow-2xl flex flex-col justify-center border border-white/20 dark:border-white/5" style="-webkit-backface-visibility: hidden; backface-visibility: hidden;">
                <div class="text-center mb-10">
                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 text-white mb-6 shadow-lg shadow-blue-500/30 transform transition-transform hover:scale-110">
                        <i class="fa-solid fa-user-graduate text-4xl"></i>
                    </div>
                    <h1 class="text-3xl font-black text-gray-800 dark:text-white mb-2">Student Portal</h1>
                    <p class="text-gray-500 dark:text-gray-400 font-medium text-sm">Welcome back to your academic workspace.</p>
                </div>

                <form class="space-y-5 loginForm">
                    <input type="hidden" name="login_type" value="student">
                    <div class="error-message hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative text-sm" role="alert">
                        <span class="block sm:inline"></span>
                    </div>
                    <div>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none transition-colors group-focus-within:text-blue-500">
                                <i class="fa-solid fa-id-card text-gray-400 group-focus-within:text-blue-500 transition-colors"></i>
                            </div>
                            <input type="text" name="reg_number" class="w-full bg-white/50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-xl py-3 pl-12 pr-4 text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-shadow shadow-sm" placeholder="Register Number (e.g. 25B95A0703)" required>
                        </div>
                    </div>

                    <div>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none transition-colors group-focus-within:text-blue-500">
                                <i class="fa-solid fa-lock text-gray-400 group-focus-within:text-blue-500 transition-colors"></i>
                            </div>
                            <input type="password" name="password" class="w-full bg-white/50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-xl py-3 pl-12 pr-10 text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-shadow shadow-sm" placeholder="Password" required>
                            <button type="button" class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 focus:outline-none" onclick="togglePassword(this)">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold py-3 rounded-xl transition-all shadow-lg hover:shadow-blue-500/40 transform hover:-translate-y-0.5 flex justify-center items-center gap-2">
                        <span>Enter Workspace</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </form>
                
                <div class="mt-6 text-center text-sm">
                    <span class="text-gray-500 dark:text-gray-400">New student?</span>
                    <button type="button" onclick="openRegisterModal('student')" class="font-bold text-blue-600 hover:text-blue-700 transition-colors">Create an account</button>
                </div>
                
                <div class="mt-6 text-center border-t border-gray-200 dark:border-gray-800 pt-6">
                    <button onclick="flipCard('back')" class="text-sm font-bold text-gray-500 hover:text-indigo-600 transition-colors flex items-center justify-center gap-2 mx-auto">
                        <i class="fa-solid fa-rotate"></i> Switch to Authorized Login
                    </button>
                </div>
            </div>

            <!-- BACK SIDE: Faculty / CR Login -->
            <div id="back" class="absolute w-full h-full backface-hidden glass p-8 md:p-12 rounded-[2.5rem] shadow-2xl border-2 border-emerald-500/20 flex flex-col justify-center" style="transform: rotateY(180deg); -webkit-backface-visibility: hidden; backface-visibility: hidden;">
                
                <!-- Glowing accent for Authorized side -->
                <div class="absolute -top-10 -right-10 w-40 h-40 bg-emerald-500 rounded-full mix-blend-multiply filter blur-[60px] opacity-40 z-0"></div>

                <div class="text-center mb-10 relative z-10">
                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white mb-6 shadow-lg shadow-emerald-500/30 transform transition-transform hover:scale-110">
                        <i class="fa-solid fa-chalkboard-user text-4xl"></i>
                    </div>
                    <h1 class="text-3xl font-black bg-clip-text text-transparent bg-gradient-to-r from-emerald-600 to-teal-600 mb-2">Authorized Access</h1>
                    <p class="text-gray-500 dark:text-gray-400 font-medium text-sm">Faculty, Admins, and Class Representatives.</p>
                </div>

                <form class="space-y-5 loginForm relative z-10">
                    <input type="hidden" name="login_type" value="authorized">
                    <div class="error-message hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative text-sm" role="alert">
                        <span class="block sm:inline"></span>
                    </div>
                    <div>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none transition-colors group-focus-within:text-emerald-500">
                                <i class="fa-solid fa-shield-halved text-gray-400 group-focus-within:text-emerald-500 transition-colors"></i>
                            </div>
                            <input type="text" name="reg_number" class="w-full bg-white/50 dark:bg-gray-800/50 border border-emerald-200 dark:border-emerald-900/50 rounded-xl py-3 pl-12 pr-4 text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-shadow shadow-sm" placeholder="Authorized ID (e.g. FACULTY1)" required>
                        </div>
                    </div>

                    <div>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none transition-colors group-focus-within:text-emerald-500">
                                <i class="fa-solid fa-key text-gray-400 group-focus-within:text-emerald-500 transition-colors"></i>
                            </div>
                            <input type="password" name="password" class="w-full bg-white/50 dark:bg-gray-800/50 border border-emerald-200 dark:border-emerald-900/50 rounded-xl py-3 pl-12 pr-10 text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-shadow shadow-sm" placeholder="Password" required>
                            <button type="button" class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 focus:outline-none" onclick="togglePassword(this)">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white font-bold py-3 rounded-xl transition-all shadow-lg hover:shadow-emerald-500/40 transform hover:-translate-y-0.5 flex justify-center items-center gap-2">
                        <span>Secure Login</span>
                        <i class="fa-solid fa-lock-open outline-2"></i>
                    </button>
                </form>
                
                <div class="mt-6 text-center text-sm relative z-10">
                    <span class="text-gray-500 dark:text-gray-400">New faculty or CR?</span>
                    <button type="button" onclick="openRegisterModal('faculty')" class="font-bold text-emerald-600 hover:text-emerald-700 transition-colors">Request Account</button>
                </div>
                
                <div class="mt-6 text-center relative z-10 border-t border-gray-200 dark:border-gray-800 pt-6">
                    <button onclick="flipCard('front')" class="text-sm font-bold text-gray-500 hover:text-emerald-600 transition-colors flex items-center justify-center gap-2 mx-auto">
                        <i class="fa-solid fa-rotate-left"></i> Back to Student Login
                    </button>
                </div>
            </div>
            
        </div>
    </div>

    <!-- Registration Modal -->
    <div id="register-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/40 dark:bg-slate-900/60 backdrop-blur-sm opacity-0 pointer-events-none transition-opacity duration-300">
        <div class="glass p-8 md:p-10 rounded-[2rem] shadow-2xl w-full max-w-md border border-white/20 transform scale-95 transition-transform duration-300" id="register-modal-content">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-black text-gray-800 dark:text-white">Sign Up</h2>
                <button onclick="closeRegisterModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>
            <form id="registerForm" class="space-y-4">
                <div class="error-message hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative text-sm"></div>
                <div class="success-message hidden bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative text-sm"></div>
                
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none transition-colors group-focus-within:text-blue-500">
                        <i class="fa-solid fa-user-shield text-gray-400 group-focus-within:text-blue-500 transition-colors"></i>
                    </div>
                    <select name="role" id="reg_role" class="w-full bg-white/50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-xl py-3 pl-12 pr-4 text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-shadow shadow-sm appearance-none" required>
                        <option value="student">Student</option>
                        <option value="cr">Class Representative</option>
                        <option value="faculty">Faculty</option>
                    </select>
                </div>

                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none transition-colors group-focus-within:text-blue-500">
                        <i class="fa-solid fa-user text-gray-400 group-focus-within:text-blue-500 transition-colors"></i>
                    </div>
                    <input type="text" name="full_name" class="w-full bg-white/50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-xl py-3 pl-12 pr-4 text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-shadow shadow-sm" placeholder="Full Name" required>
                </div>
                
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none transition-colors group-focus-within:text-blue-500">
                        <i class="fa-solid fa-id-card text-gray-400 group-focus-within:text-blue-500 transition-colors"></i>
                    </div>
                    <input type="text" name="reg_number" class="w-full bg-white/50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-xl py-3 pl-12 pr-4 text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-shadow shadow-sm" placeholder="Register Number" required>
                </div>

                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none transition-colors group-focus-within:text-blue-500">
                        <i class="fa-solid fa-lock text-gray-400 group-focus-within:text-blue-500 transition-colors"></i>
                    </div>
                    <input type="password" name="password" id="reg_password" class="w-full bg-white/50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-xl py-3 pl-12 pr-10 text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-shadow shadow-sm" placeholder="Password (Min 6 chars)" required>
                    <button type="button" class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 focus:outline-none" onclick="togglePassword(this)">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>

                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none transition-colors group-focus-within:text-blue-500">
                        <i class="fa-solid fa-lock text-gray-400 group-focus-within:text-blue-500 transition-colors"></i>
                    </div>
                    <input type="password" name="confirm_password" id="reg_confirm_password" class="w-full bg-white/50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-xl py-3 pl-12 pr-10 text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-shadow shadow-sm" placeholder="Confirm Password" required>
                    <button type="button" class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 focus:outline-none" onclick="togglePassword(this)">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>

                <button type="submit" class="w-full mt-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold py-3 rounded-xl transition-all shadow-lg hover:shadow-blue-500/40 flex justify-center items-center gap-2">
                    <span>Create Account</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Theme Toggle -->
    <button id="theme-toggle" class="fixed bottom-5 right-5 w-12 h-12 rounded-full glass border border-gray-200 dark:border-gray-700 flex items-center justify-center text-gray-600 hover:text-blue-600 transition-colors z-50">
        <i class="fa-solid fa-moon"></i>
    </button>

    <script>
        // 3D Flip Logic
        const card = document.getElementById('login-card');
        function flipCard(side) {
            if(side === 'back') {
                card.style.transform = 'rotateY(180deg)';
            } else {
                card.style.transform = 'rotateY(0deg)';
            }
        }

        // Register Modal Logic
        const regModal = document.getElementById('register-modal');
        const regModalContent = document.getElementById('register-modal-content');
        
        function openRegisterModal(defaultRole = 'student') {
            document.getElementById('reg_role').value = defaultRole;
            regModal.classList.remove('opacity-0', 'pointer-events-none');
            regModalContent.classList.remove('scale-95');
            regModalContent.classList.add('scale-100');
        }

        function closeRegisterModal() {
            regModal.classList.add('opacity-0', 'pointer-events-none');
            regModalContent.classList.remove('scale-100');
            regModalContent.classList.add('scale-95');
        }

        // Password Visibility Toggle
        function togglePassword(button) {
            const input = button.previousElementSibling;
            const icon = button.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Theme Toggle Logic
        document.getElementById('theme-toggle').addEventListener('click', () => {
            document.documentElement.classList.toggle('dark');
            const icon = document.querySelector('#theme-toggle i');
            if(document.documentElement.classList.contains('dark')) {
                icon.classList.remove('fa-moon');
                icon.classList.add('fa-sun');
            } else {
                icon.classList.remove('fa-sun');
                icon.classList.add('fa-moon');
            }
        });

        // Form Submission Logic
        document.querySelectorAll('.loginForm').forEach(form => {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const btn = e.target.querySelector('button[type="submit"]');
                const originalBtnContent = btn.innerHTML;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Authenticating...';
                btn.disabled = true;

                const formData = new FormData(e.target);
                try {
                    const response = await fetch('api/auth.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json();
                    
                    if(data.success) {
                        window.location.href = 'dashboard.php';
                    } else {
                        const err = e.target.querySelector('.error-message');
                        err.querySelector('span').innerText = data.message || 'Login failed';
                        err.classList.remove('hidden');
                        btn.innerHTML = originalBtnContent;
                        btn.disabled = false;
                    }
                } catch(err) {
                    console.error(err);
                    const errBox = e.target.querySelector('.error-message');
                    errBox.querySelector('span').innerText = 'Connection error.';
                    errBox.classList.remove('hidden');
                    btn.innerHTML = originalBtnContent;
                    btn.disabled = false;
                }
            });
        });

        // Register Form Logic
        const registerForm = document.getElementById('registerForm');
        if (registerForm) {
            registerForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const btn = e.target.querySelector('button[type="submit"]');
                const originalBtnContent = btn.innerHTML;
                const errBox = e.target.querySelector('.error-message');
                const successBox = e.target.querySelector('.success-message');
                
                errBox.classList.add('hidden');
                successBox.classList.add('hidden');
                
                const pwd = document.getElementById('reg_password').value;
                const cpwd = document.getElementById('reg_confirm_password').value;
                if(pwd !== cpwd) {
                    errBox.innerText = 'Passwords do not match';
                    errBox.classList.remove('hidden');
                    return;
                }

                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Creating...';
                btn.disabled = true;

                try {
                    const response = await fetch('api/register.php', {
                        method: 'POST',
                        body: new FormData(e.target)
                    });
                    const data = await response.json();
                    
                    if(data.success) {
                        successBox.innerText = 'Registration successful! Redirecting...';
                        successBox.classList.remove('hidden');
                        setTimeout(() => window.location.href = 'dashboard.php', 1000);
                    } else {
                        errBox.innerText = data.message || 'Registration failed';
                        errBox.classList.remove('hidden');
                        btn.innerHTML = originalBtnContent;
                        btn.disabled = false;
                    }
                } catch(err) {
                    errBox.innerText = 'Connection error.';
                    errBox.classList.remove('hidden');
                    btn.innerHTML = originalBtnContent;
                    btn.disabled = false;
                }
            });
        }

        // Interactive 3D Particle Starfield Background
        document.addEventListener('DOMContentLoaded', () => {
             const container = document.getElementById('bg-canvas');
             const scene = new THREE.Scene();
             // Fog to blend particles smoothly
             scene.fog = new THREE.FogExp2(0x0f172a, 0.001);

             const camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
             camera.position.z = 400;

             const renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true });
             renderer.setSize(window.innerWidth, window.innerHeight);
             renderer.setPixelRatio(window.devicePixelRatio);
             container.appendChild(renderer.domElement);

             const particleCount = 2000;
             const geometry = new THREE.BufferGeometry();
             const positions = [];
             const velocities = [];

             for (let i = 0; i < particleCount; i++) {
                 positions.push((Math.random() - 0.5) * 2000); // x
                 positions.push((Math.random() - 0.5) * 2000); // y
                 positions.push((Math.random() - 0.5) * 2000); // z
                 velocities.push({
                     x: (Math.random() - 0.5) * 0.5,
                     y: (Math.random() - 0.5) * 0.5
                 });
             }

             geometry.setAttribute('position', new THREE.Float32BufferAttribute(positions, 3));

             const material = new THREE.PointsMaterial({
                 color: 0x3b82f6,
                 size: 3,
                 transparent: true,
                 opacity: 0.6,
                 blending: THREE.AdditiveBlending
             });

             const stars = new THREE.Points(geometry, material);
             scene.add(stars);

             let mouseX = 0;
             let mouseY = 0;
             let targetX = 0;
             let targetY = 0;

             document.addEventListener('mousemove', (e) => {
                 mouseX = (e.clientX - window.innerWidth / 2);
                 mouseY = (e.clientY - window.innerHeight / 2);
             });

             const isDark = document.documentElement.classList.contains('dark');
             if(!isDark) { material.color.setHex(0x60a5fa); }

             // Theme change observer to change particle color
             const observer = new MutationObserver(() => {
                 if (document.documentElement.classList.contains('dark')) {
                     material.color.setHex(0x3b82f6);
                     scene.fog.color.setHex(0x0f172a);
                 } else {
                     material.color.setHex(0x60a5fa);
                     scene.fog.color.setHex(0xf8fafc);
                 }
             });
             observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

             function animate() {
                 requestAnimationFrame(animate);
                 
                 targetX = mouseX * 0.001;
                 targetY = mouseY * 0.001;
                 
                 stars.rotation.y += 0.002;
                 stars.rotation.x += (targetY - stars.rotation.x) * 0.05;
                 stars.rotation.y += (targetX - stars.rotation.y) * 0.05;

                 renderer.render(scene, camera);
             }
             animate();

             window.addEventListener('resize', () => {
                 camera.aspect = window.innerWidth / window.innerHeight;
                 camera.updateProjectionMatrix();
                 renderer.setSize(window.innerWidth, window.innerHeight);
             });
        });

        // Custom Cursor Logic
        document.addEventListener('DOMContentLoaded', () => {
            const cursor = document.createElement('div');
            cursor.classList.add('custom-cursor');
            document.body.appendChild(cursor);

            document.addEventListener('mousemove', (e) => {
                cursor.style.left = e.clientX + 'px';
                cursor.style.top = e.clientY + 'px';
            });

            document.addEventListener('mousedown', () => cursor.style.transform = 'translate(-50%, -50%) scale(0.7)');
            document.addEventListener('mouseup', () => cursor.style.transform = 'translate(-50%, -50%) scale(1)');

            // Hover effect on interactive elements
            const interactiveElements = document.querySelectorAll('a, button, input, select, textarea, [onclick]');
            interactiveElements.forEach(el => {
                el.addEventListener('mouseenter', () => cursor.classList.add('hovering'));
                el.addEventListener('mouseleave', () => cursor.classList.remove('hovering'));
            });
        });

    </script>
</body>
</html>
