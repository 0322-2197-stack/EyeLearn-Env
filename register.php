<?php
require_once 'config.php';

$error = '';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name']);
    $lastName = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $gender = $_POST['gender'];
    $section = trim($_POST['section']);
    
    // Validate inputs
    if (empty($firstName) || empty($lastName) || empty($email) || empty($password) || empty($gender) || empty($section)) {
        $error = 'Please fill in all required fields';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif (preg_match('/@admin\.eyelearn$/', $email)) {
        $error = 'Admin accounts cannot be registered through this form';
    } else {
        // Check if user exists
        if (userExists($email, $pdo)) {
            $error = 'Email is already registered';
        } else {
            // Register user (only as student)
            if (registerUser($firstName, $lastName, $email, $password, $gender, $section, $pdo)) {
                // Set success message and redirect to login page
                $_SESSION['registration_success'] = 'Registration successful! You can now log in.';
                header('Location: loginpage.php');
                exit;
            } else {
                $error = 'Registration failed. Please try again later.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - EyeLearn</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary': '#4F46E5',
                        'secondary': '#10B981',
                        'accent': '#F97316',
                        'background': '#F9FAFB'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-background min-h-screen flex items-center justify-center py-12">
    <div class="max-w-md w-full bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="md:flex">
            <div class="w-full p-6">
                <div class="flex justify-center mb-6">
                    <div class="flex items-center">
                        <i class="fas fa-eye text-primary text-3xl mr-2"></i>
                        <h1 class="text-3xl font-bold text-primary">EyeLearn</h1>
                    </div>
                </div>
                
                <h2 class="text-2xl font-bold text-center text-gray-800 mb-8">Create Your Student Account</h2>
                
                <?php if (!empty($error)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <span class="block sm:inline"><?php echo $error; ?></span>
                    </div>
                <?php endif; ?>
                
                <form action="register.php" method="POST">
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="first_name">
                                First Name
                            </label>
                            <input class="appearance-none border rounded-lg w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-primary" 
                                   id="first_name" type="text" name="first_name" placeholder="John" required>
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="last_name">
                                Last Name
                            </label>
                            <input class="appearance-none border rounded-lg w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-primary" 
                                   id="last_name" type="text" name="last_name" placeholder="Smith" required>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
                            Email (must be student email)
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-envelope text-gray-400"></i>
                            </div>
                            <input class="appearance-none border rounded-lg w-full py-3 px-4 pl-10 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-primary" 
                                   id="email" type="email" name="email" placeholder="student@university.edu" required>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="section">
                            Section
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-users text-gray-400"></i>
                            </div>
                            <select class="appearance-none border rounded-lg w-full py-3 px-4 pl-10 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-primary bg-white" 
                                    id="section" name="section" required>
                                <option value="">Select Section</option>
                                <option value="BSINFO-1A">BSINFO-1A</option>
                                <option value="BSINFO-1B">BSINFO-1B</option>
                                <option value="BSINFO-1C">BSINFO-1C</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <i class="fas fa-chevron-down text-gray-400"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
                            Password
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400"></i>
                            </div>
                            <input class="appearance-none border rounded-lg w-full py-3 px-4 pl-10 pr-10 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-primary" 
                                   id="password" type="password" name="password" placeholder="••••••••" required>
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                <button type="button" id="togglePassword" class="text-gray-400 hover:text-gray-600 focus:outline-none">
                                    <i class="fas fa-eye" id="passwordIcon"></i>
                                </button>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Password must be at least 6 characters</p>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="confirm_password">
                            Confirm Password
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400"></i>
                            </div>
                            <input class="appearance-none border rounded-lg w-full py-3 px-4 pl-10 pr-10 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-primary" 
                                   id="confirm_password" type="password" name="confirm_password" placeholder="••••••••" required>
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                <button type="button" id="toggleConfirmPassword" class="text-gray-400 hover:text-gray-600 focus:outline-none">
                                    <i class="fas fa-eye" id="confirmPasswordIcon"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">
                            Gender
                        </label>
                        <div class="flex space-x-4">
                            <div class="flex items-center">
                                <input id="gender-male" name="gender" type="radio" value="Male" class="h-4 w-4 text-primary focus:ring-primary border-gray-300" required>
                                <label for="gender-male" class="ml-2 block text-sm text-gray-700">
                                    Male
                                </label>
                            </div>
                            <div class="flex items-center">
                                <input id="gender-female" name="gender" type="radio" value="Female" class="h-4 w-4 text-primary focus:ring-primary border-gray-300">
                                <label for="gender-female" class="ml-2 block text-sm text-gray-700">
                                    Female
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <button class="w-full bg-primary hover:bg-indigo-700 text-white font-bold py-3 px-4 rounded-lg focus:outline-none focus:shadow-outline transition-colors duration-200" 
                                type="submit">
                            Create Student Account
                        </button>
                    </div>
                </form>
                
                <div class="text-center">
                    <p class="text-gray-600 text-sm">
                        Already have an account? 
                        <a href="loginpage.php" class="text-primary hover:text-indigo-700 font-medium">Sign in</a>
                    </p>
                </div>
                
                <div class="mt-6 text-center">
                    <p class="text-xs text-gray-500">
                        By creating an account, you agree to our Terms of Service and Privacy Policy
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Password toggle functionality
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            const passwordIcon = document.getElementById('passwordIcon');
            
            togglePassword.addEventListener('click', function() {
                // Toggle the password field type
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                
                // Toggle the eye icon
                if (type === 'password') {
                    passwordIcon.classList.remove('fa-eye-slash');
                    passwordIcon.classList.add('fa-eye');
                } else {
                    passwordIcon.classList.remove('fa-eye');
                    passwordIcon.classList.add('fa-eye-slash');
                }
            });
            
            // Confirm password toggle functionality
            const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const confirmPasswordIcon = document.getElementById('confirmPasswordIcon');
            
            toggleConfirmPassword.addEventListener('click', function() {
                // Toggle the confirm password field type
                const type = confirmPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                confirmPasswordInput.setAttribute('type', type);
                
                // Toggle the eye icon
                if (type === 'password') {
                    confirmPasswordIcon.classList.remove('fa-eye-slash');
                    confirmPasswordIcon.classList.add('fa-eye');
                } else {
                    confirmPasswordIcon.classList.remove('fa-eye');
                    confirmPasswordIcon.classList.add('fa-eye-slash');
                }
            });
        });
    </script>
</body>
</html>