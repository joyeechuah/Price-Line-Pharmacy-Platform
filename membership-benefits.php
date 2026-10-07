<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Membership Benefits | Price Line Pharmacy</title>

    <link rel="stylesheet" href="CSS/membership-benefits.css">
</head>

<body class="membership-page">

    <!-- Add your existing website header and navigation here. -->

    <main>
        <section class="membership-intro">
            <p class="membership-label">PRICE LINE PHARMACY MEMBERSHIP</p>

            <h1>Your health journey starts here.</h1>

            <p>
                Create a customer account to shop for health essentials,
                view your orders, and request a pharmacist consultation.
            </p>

            <a href="register.php" class="membership-button">
                Create an Account
            </a>

            <p class="membership-login">
                Already have an account?
                <a href="login.php">Log In</a>
            </p>
        </section>

        <section class="membership-benefits"
                 aria-labelledby="benefits-heading">

            <h2 id="benefits-heading">Your Member Benefits</h2>

            <div class="benefits-grid">
                <article class="benefit-card">
                    <span class="benefit-number" aria-hidden="true">01</span>

                    <h3>Shop Online</h3>

                    <p>
                        Add products to your cart, adjust quantities,
                        and place an order with your delivery details.
                    </p>
                </article>

                <article class="benefit-card">
                    <span class="benefit-number" aria-hidden="true">02</span>

                    <h3>View Your Orders</h3>

                    <p>
                        Find your previous orders and review the
                        products you purchased in one place.
                    </p>
                </article>

                <article class="benefit-card">
                    <span class="benefit-number" aria-hidden="true">03</span>

                    <h3>Request a Consultation</h3>

                    <p>
                        Send your question and preferred contact time
                        for a pharmacist to follow up.
                    </p>
                </article>
            </div>
        </section>

        <section class="membership-steps"
                 aria-labelledby="join-heading">

            <h2 id="join-heading">How to Get Started</h2>

            <ol>
                <li>Create your customer account.</li>
                <li>Log in using your email and password.</li>
                <li>Browse products or submit a consultation request.</li>
            </ol>

            <a href="register.php" class="membership-button">
                Register Now
            </a>
        </section>
    </main>

    <footer class="home-footer">
    <div class="footer-content">

        <div class="footer-brand">
            <h2>Price <span>Line</span> Pharmacy</h2>
            <p>Your Health, Delivered Fast and Safe.</p>
            <p>
                Explore everyday health essentials,
                discover membership benefits and
                request a pharmacist consultation.
            </p>
        </div>

        <div class="footer-links">
            <h3>Explore</h3>
            <a href="homepage.php">Home</a>
            <a href="products.php">Products</a>
            <a href="membership-benefits.php">
                Membership Benefits
            </a>
            <a href="customer/consultation.php">
                E-Consult Now
            </a>
        </div>

        <div class="footer-links">
            <h3>Your Account</h3>
            <a href="login.php">Log In</a>
            <a href="register.php">Create an Account</a>
            <a href="prescription.php">
                Upload e-Prescription
            </a>
        </div>

    </div>

    <div class="footer-bottom">
        <p>&copy; 2026 Price Line Pharmacy.</p>
        <a href="#page-top">Back to top ↑</a>
    </div>
    </footer>

</body>
</html>