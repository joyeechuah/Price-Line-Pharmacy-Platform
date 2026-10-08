# Mini Project Journal

**Student Name: Chuah Jo Yee
**Project Title: Pharmacy Online Platform
**Start Date: 24/9/2026
**End Date: 14/10/2026

---

## Project Overview

> Briefly describe your project idea, what problem it solves, and what you aim to build.

A web application where customers browse, search and order pharmacy products online, with a cart, checkout and pharmacist consultation requests.

Customers often need to visit a pharmacy in person to buy everyday medicines and health products, check whether an item is in stock, or ask a pharmacist a question. This platform is for pharmacy customers who want to shop from home, and for pharmacy staff who need one place to manage products, stock and customer enquiries. Customers can search products by category, order online and request a call from a pharmacist. Staff get separate, role-limited tools, so each person only handles the work they are responsible for.


## Tech Stack

> List the technologies, frameworks, and tools you plan to use.

- **Frontend: HTML CSS JavaScript
- **Backend: PHP
- **Database: MySQL
- **Other:**

---

## Day 1 — Date: 30/9/2026

### What I planned to do today
Complete homepage.php and membership-benefits.php.

### What I actually did
Refined the homepage header, logo, title, slogan, and navigation.
Positioned Log In and Register at the top-right corner.
Added a Bootstrap carousel to the hero banner area.
Styled the product search area and category sections.
Built “Hot Items” product cards with images, names, prices, and Browse Products buttons.
Adjusted the cards so their prices and buttons aligned.
Started planning the membership-benefits.php page.
Reviewed my HTML and CSS for errors.

### Blockers / Challenges
Repeated CSS rules caused conflicting styles, affecting the title’s position and background colour.
Missing commas between selectors prevented styles from applying to both category sections.
Different product-name lengths caused uneven price and button positions.
The skincare banner had different proportions from the vitamins banner, making it difficult to match their sizes without cropping or stretching.
Incorrect HTML closing-tag order and image paths needed correction.
Some skincare product details were still unfinished.

### What I learned
A comma groups CSS selectors, while > selects direct children.
Later CSS rules can override earlier rules with equal specificity.
CSS Grid helps organise layouts, while Flexbox and margin-top: auto can align content within product cards.
object-fit: contain, cover, and fill handle images differently: preserving the whole image, cropping it, or stretching it.
HTML elements must close in the correct nesting order, and image URLs should use forward slashes.
Responsive styles are needed to keep navigation and product cards usable on smaller screens.

---

## Day 2 — Date: 1/10/2026

### What I planned to do today
Complete homepage.php by today, start working with register.php and login.php.

### What I actually did
Planned the registration and login features for my pharmacy website.
Reviewed the proposal’s ER diagram and drafted SQL for the price_line_pharmacy database and users table.
Reviewed changes needed to match the ERD, including user_id, name, and the missing role column.
Worked on making the navigation bar sticky.
Improved the appearance of the top-right Log In and Register links with button styling and blue-and-green colours.

### Blockers / Challenges
My initial SQL used different column names from the ERD and did not include user roles.
I needed guidance on connecting the database structure to registration and login.
Some CSS properties, such as box-shadow, were unfamiliar, so I chose simpler styling.
The account links needed more visual emphasis against the header background.

### What I learned
User roles distinguish customers from admins, pharmacists, and storekeepers.
Passwords should be stored as hashes, and email addresses should be unique.
CREATE TABLE creates a table, while ALTER TABLE updates an existing structure.
position: sticky, top: 0, and z-index work together to keep navigation visible above other content.
Padding, borders, background colours, and hover styles can make links more noticeable and easier to click.

---

## Day 3 — Date: 4/10/2026

### What I planned to do today
Continue developing the pharmacy homepage.
Improve the layout and styling of product sections.
Set up the database and begin the admin consultation page.

### What I actually did
Developed the homepage with navigation, promotional banners, a search bar and “Hot Items” product cards.
Added a Bootstrap carousel for the main banners.
Adjusted the heading, account links, product prices and buttons to improve alignment.
Worked on consistent styling for the vitamins and skincare sections.
Organised project files into customer, admin, pharmacist and storekeeper folders.
Worked on the database connection and the users and consultations tables.
Built the consultation page layout and worked on displaying requests and handling action buttons.

### Blockers / Challenges
- Different product-name lengths caused prices and buttons to appear uneven.
- Banner images had different proportions, making their displayed sizes inconsistent.
- Some CSS rules overlapped or did not apply to the intended section.
- A MariaDB tablespace error interrupted database setup.
- Undefined variables and a duplicated escape() function caused PHP errors.
- Connecting forms to database actions was more challenging than creating the page layout.
What I learned

### What I learned
How CSS spacing, image sizing and layout rules affect page consistency.
How the > selector targets direct child elements.
How PHP and HTML work together to display database records.
Why variables must be initialised before use and functions should not be declared twice.
How prepared statements pass form values to database queries.
Why output should be escaped with htmlspecialchars().
How breaking a page into small steps makes debugging easier.

---

## Day 4 — Date: 5/10/2026

### What I planned to do today
Complete and test registration and login.
Set up logout and admin access checks.
Continue developing the admin pages.
Upload the updated admin files to GitHub.

### What I actually did
Connected registration and login to the database and confirmed that the password process worked.
Styled the registration page to match the login page.
Worked on redirecting users to the appropriate page after login.
Added shared authentication checks through includes/auth.php and admin role checks.
Prepared initial versions of the admin orders, users and products pages.

### Blockers / Challenges
Login initially redirected to the homepage instead of the intended page.

### What I learned
Login, logout and access checks must use consistent session variables.
Authentication checks whether someone is logged in, while role checks control access to admin pages.
---
---

## Day 5 — Date: 6/10/2026

### What I planned to do today
Rearrange the 1 style.css file to different seperated CSS files. Start with products.php

### What I actually did


### Blockers / Challenges


### What I learned


---

## Day 6 — Date: ____

### What I planned to do today


### What I actually did


### Blockers / Challenges


### What I learned


---

## Day 7 — Date: ____

### What I planned to do today


### What I actually did


### Blockers / Challenges


### What I learned


---

## Day 8 — Date: ____

### What I planned to do today


### What I actually did


### Blockers / Challenges


### What I learned


---

## Day 9 — Date: ____

### What I planned to do today


### What I actually did


### Blockers / Challenges


### What I learned


---

## Day 10 — Date: ____

### What I planned to do today


### What I actually did


### Blockers / Challenges


### What I learned


---

## Day 11 — Date: ____

### What I planned to do today


### What I actually did


### Blockers / Challenges


### What I learned


---

## Day 12 — Date: ____

### What I planned to do today


### What I actually did


### Blockers / Challenges


### What I learned


---

## Day 13 — Date: ____

### What I planned to do today


### What I actually did


### Blockers / Challenges


### What I learned


---

## Day 14 — Date: ____

### What I planned to do today


### What I actually did


### Blockers / Challenges


### What I learned


---

## Final Reflection

### What went well?


### What would I do differently?


### Key takeaways from this project


