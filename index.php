<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JKKMCT Quiz Competition</title>
    <style>
        :root {
            --primary: #4F46E5;
            --primary-hover: #4338CA;
            --background: #F3F4F6;
            --card-bg: #FFFFFF;
            --text-main: #1F2937;
            --text-muted: #6B7280;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--background);
            color: var(--text-main);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }

        .container {
            background-color: var(--card-bg);
            padding: 3rem;
            border-radius: 12px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            text-align: center;
            max-width: 600px;
            width: 90%;
        }

        h1 {
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        p {
            color: var(--text-muted);
            margin-bottom: 2rem;
            line-height: 1.5;
        }

        .buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s;
            cursor: pointer;
            border: none;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
            box-shadow: 0 4px 6px rgba(79, 70, 229, 0.2);
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(79, 70, 229, 0.3);
        }

        .btn-outline {
            background-color: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-outline:hover {
            background-color: var(--primary);
            color: white;
        }
        
        .footer {
            margin-top: 3rem;
            color: var(--text-muted);
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>JKKMCT Quiz Portal</h1>
        <p>Welcome to the official online quiz competition portal for JKKMCT. Students can register for upcoming competitions and participate in a secure evaluating environment.</p>
        
        <div class="buttons">
            <a href="student/register.php" class="btn btn-primary">Student Registration</a>
            <a href="student/login.php" class="btn btn-outline">Student Login</a>
            <a href="admin/login.php" class="btn btn-outline" style="border-color: #4b5563; color: #4b5563;">Admin Portal</a>
        </div>
    </div>
    <div class="footer">
        &copy; <?php echo date("Y"); ?> JKKMCT Quiz Competition System
    </div>
</body>
</html>
