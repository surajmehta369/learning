<?php
include("conn.php");
session_name('STUDENT_SESSION');
session_start();
// session_start();
// if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
//     header("Location: login/login.php");
//     exit;
// }
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Subjects & Interview Questions — Bootstrap Page</title>
  <link rel="icon" type="image/png" href="images/favicon.png">
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <?php include("header.php"); ?>

  <style>
    body {
      margin: 0;
      padding: 0;
      font-family: Arial, sans-serif;
      background: #fff;
      /* plain background */
      color: #111;
    }

    .quote {
      margin-top: 30px;
      margin-bottom: 20px;
    }

    .section-title {
      margin: 40px 0 20px 0;
      text-align: center;
      font-weight: 600;
    }

    .subject-card,
    .interview-card {
      transition: transform 0.2s, background-color 0.2s, border-color 0.2s;
      border: 2px solid #007bff;
    }

    .subject-card:hover,
    .interview-card:hover {
      transform: scale(1.05);
      background-color: #f8f9fa;
      border-color: #000;
    }

    .card-title {
      font-weight: 600;
    }

    .card-text {
      font-size: 0.9rem;
      color: #555;
    }

    .search-input {
      max-width: 400px;
      margin: 15px auto;
    }

    .btn.btn-purple {
      margin: 15px;
    }

    .hero {
      width: 100vw;
      /* force full viewport width */
      min-height: 55vh;
      margin: 0;
      padding: 60px 20px;
      /* some breathing room for content */
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      text-align: center;

      background: url('images/notes.png') no-repeat center center;
      background-size: cover;
    }


    .hero h1 {
      font-size: 48px;
      font-weight: 700;
      margin-bottom: 20px;
    }

    .hero .highlight {
      background: #ffe680;
      padding: 0 6px;
    }

    .search-box {
      display: flex;
      width: 100%;
      max-width: 700px;
      box-shadow:
        0 6px 15px rgba(0, 0, 0, 0.15),
        0 12px 30px rgba(0, 0, 0, 0.1);
      /* deeper shadow */
    }

    .search-box input {
      flex: 1;
      padding: 18px 20px;
      border: none;
      outline: none;
      font-size: 18px;

    }

    .search-box button {
      background: #c94c3b;
      /* red button */
      color: #fff;
      border: none;
      padding: 18px 32px;
      font-size: 16px;
      font-weight: bold;
      cursor: pointer;
      transition: background 0.3s ease;
    }

    .search-box button:hover {
      background: #a63828;
    }

    body {
      padding-top: 70px;
      /* adjust depending on navbar height */
    }

    .subject-card,
    .interview-card {
      border: none;
      box-shadow: 0 6px 18px rgba(0, 0, 0, 0.15);
      /* stronger shadow */
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      position: relative;
      overflow: hidden;
    }

    .subject-card:hover,
    .interview-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 12px 28px rgba(0, 0, 0, 0.2);
    }

    /* Icon circle (same as before but more bold) */
    .icon-circle {
      width: 65px;
      height: 65px;
      margin: 0 auto;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #f0f0f0;
      color: #c94c3b;
      font-size: 28px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    /* Arrow inside card */
    .card-arrow {
      font-size: 24px;
      color: black;
      display: inline-block;
      transform: rotate(-270deg);
      /* default */
      transition: transform 0.8s ease;
      margin-top: 12px;
    }

    .subject-card:hover .card-arrow,
    .interview-card:hover .card-arrow {
      transform: rotate(-270deg);
      /* hover */
    }
  </style>
</head>

<body>

  <!-- Hero Section with Search (outside container) -->
  <div class="hero">
    <h1><span class="highlight">Master the Classics</span></h1>

    <div class="search-box">
      <input type="text" id="searchBox" placeholder="Search here ...">
      <button>Search</button>
    </div>
  </div>

  <div class="container py-4">
    <h4 class="section-title">Handwritten-notes</h4>
    <!-- Subjects Section -->
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4" id="subjectGrid">
      <div class="col" data-subject="C-Language">
        <a href="https://drive.google.com/file/d/10QuWjMAuFAXPiWWbIQY93klfW3Z4pzif/view?usp=drivesdk" target="_blank" class="text-decoration-none">
          <div class="card subject-card text-center h-100">
            <div class="card-body">
              <h5 class="card-title">C-Language</h5>
              <p class="card-text">Free Notes | Click to view &nbsp; &nbsp;<span class="card-arrow">&#8595;</span> <!-- Down arrow --></p>
            </div>
          </div>
        </a>
      </div>

      <div class="col" data-subject="Cloud Computing">
        <a href="https://drive.google.com/file/d/10869gr-PM7NS044GctNgZRegkHkqdqCM/view?usp=drivesdk" target="_blank" class="text-decoration-none">
          <div class="card subject-card text-center h-100">
            <div class="card-body">
              <h5 class="card-title">Cloud Computing</h5>
              <p class="card-text">Free Notes | Click to view&nbsp; &nbsp;<span class="card-arrow">&#8595;</span></p>
            </div>
          </div>
        </a>
      </div>
      <div class="col" data-subject="DBMS">
        <a href="https://drive.google.com/file/d/10Ub0dwjzJxftoOKGvTqVs2dd7MQEpADn/view?usp=drivesdk" target="_blank" class="text-decoration-none">
          <div class="card subject-card text-center h-100">
            <div class="card-body">
              <h5 class="card-title">DBMS</h5>
              <p class="card-text">Free Notes | Click to view&nbsp; &nbsp;<span class="card-arrow">&#8595;</span></p>
            </div>
          </div>
        </a>
      </div>
      <div class="col" data-subject="Information Security">
        <a href="https://drive.google.com/file/d/10JZVHAc4UI4Suc6PgPBbBSf4zzbIoDyZ/view?usp=drivesdk" target="_blank" class="text-decoration-none">
          <div class="card subject-card text-center h-100">
            <div class="card-body">
              <h5 class="card-title">Information Security</h5>
              <p class="card-text">Free Notes | Click to view&nbsp; &nbsp;<span class="card-arrow">&#8595;</span></p>
            </div>
          </div>
        </a>
      </div>
      <div class="col" data-subject="Python">
        <a href="https://drive.google.com/file/d/10opnJGoLz9nrTPxZKkKiAg2UbB3uQQ5E/view?usp=drivesdk" target="_blank" class="text-decoration-none">
          <div class="card subject-card text-center h-100">
            <div class="card-body">
              <h5 class="card-title">Python</h5>
              <p class="card-text">Free Notes | Click to view&nbsp; &nbsp;<span class="card-arrow">&#8595;</span></p>
            </div>
          </div>
        </a>
      </div>
      <div class="col" data-subject="Software Engineering">
        <a href="https://drive.google.com/file/d/10d5oy_eLP7j6QkUls-VwTYXN2d1rIUkW/view?usp=drivesdk" target="_blank" class="text-decoration-none">
          <div class="card subject-card text-center h-100">
            <div class="card-body">
              <h5 class="card-title">Software Engineering</h5>
              <p class="card-text">Free Notes | Click to view&nbsp; &nbsp;<span class="card-arrow">&#8595;</span></p>
            </div>
          </div>
        </a>
      </div>
      <div class="col" data-subject="Web Technology">
        <a href="https://drive.google.com/file/d/10gepUK-RFheeCz4c7v1SAAuYfoJAf0x8/view?usp=drivesdk" target="_blank" class="text-decoration-none">
          <div class="card subject-card text-center h-100">
            <div class="card-body">
              <h5 class="card-title">Web Technology</h5>
              <p class="card-text">Free Notes | Click to view&nbsp; &nbsp;<span class="card-arrow">&#8595;</span></p>
            </div>
          </div>
        </a>
      </div>
    </div>

    <!-- Interview Questions Section -->
    <h4 class="section-title">Interview Questions</h4>
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4" id="interviewGrid">
      <div class="col" data-interview="PHP Laravel">
        <a href="e-books/php%20laravel.pdf" target="_blank" class="text-decoration-none">
          <div class="card interview-card text-center h-100">
            <div class="card-body">
              <h5 class="card-title">PHP Laravel</h5>
              <p class="card-text">Free Questions | Click to view&nbsp; &nbsp;<span class="card-arrow">&#8595;</span></p>
            </div>
          </div>
        </a>
      </div>
      <div class="col" data-interview="Python">
        <a href="e-books/python.pdf" target="_blank" class="text-decoration-none">
          <div class="card interview-card text-center h-100">
            <div class="card-body">
              <h5 class="card-title">Python</h5>
              <p class="card-text">Free Questions | Click to view&nbsp; &nbsp;<span class="card-arrow">&#8595;</span></p>
            </div>
          </div>
        </a>
      </div>
      <div class="col" data-interview="Data Science">
        <a href="e-books/datascience.pdf" target="_blank" class="text-decoration-none">
          <div class="card interview-card text-center h-100">
            <div class="card-body">
              <h5 class="card-title">Data Science</h5>
              <p class="card-text">Free Questions | Click to view&nbsp; &nbsp;<span class="card-arrow">&#8595;</span></p>
            </div>
          </div>
        </a>
      </div>
      <div class="col" data-interview="Web Development">
        <a href="e-books/web%20development.pdf" target="_blank" class="text-decoration-none">
          <div class="card interview-card text-center h-100">
            <div class="card-body">
              <h5 class="card-title">Web Development</h5>
              <p class="card-text">Free Questions | Click to view&nbsp; &nbsp;<span class="card-arrow">&#8595;</span></p>
            </div>
          </div>
        </a>
      </div>

 <div class="col" data-interview="Javascript">
        <a href="e-books/javascript.pdf" target="_blank" class="text-decoration-none">
          <div class="card interview-card text-center h-100">
            <div class="card-body">
              <h5 class="card-title">Javascript</h5>
              <p class="card-text">Free Questions | Click to view&nbsp; &nbsp;<span class="card-arrow">&#8595;</span></p>
            </div>
          </div>
        </a>
      </div>

       <div class="col" data-interview="Javascript">
        <a href="e-books/jquery.pdf" target="_blank" class="text-decoration-none">
          <div class="card interview-card text-center h-100">
            <div class="card-body">
              <h5 class="card-title">JQuery</h5>
              <p class="card-text">Free Questions | Click to view&nbsp; &nbsp;<span class="card-arrow">&#8595;</span></p>
            </div>
          </div>
        </a>
      </div>

      <div class="col" data-interview="React.js">
        <a href="e-books/React.pdf" target="_blank" class="text-decoration-none">
          <div class="card interview-card text-center h-100">
            <div class="card-body">
              <h5 class="card-title">React.js</h5>
              <p class="card-text">Free Questions | Click to view&nbsp; &nbsp;<span class="card-arrow">&#8595;</span></p>
            </div>
          </div>
        </a>
      </div>
      <div class="col" data-interview="Node.js">
        <a href="e-books/Node.js.pdf" target="_blank" class="text-decoration-none">
          <div class="card interview-card text-center h-100">
            <div class="card-body">
              <h5 class="card-title">Node.js</h5>
              <p class="card-text">Free Questions | Click to view&nbsp; &nbsp;<span class="card-arrow">&#8595;</span></p>
            </div>
          </div>
        </a>
      </div>

      



      <div class="col" data-interview="Github ">
        <a href="e-books/Github.pdf" target="_blank" class="text-decoration-none">
          <div class="card interview-card text-center h-100">
            <div class="card-body">
              <h5 class="card-title">GIT & GITHUB BASICS </h5>
              <p class="card-text">Free Questions | Click to view&nbsp; &nbsp;<span class="card-arrow">&#8595;</span></p>
            </div>
          </div>
        </a>
      </div>
    </div>

  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const searchBox = document.getElementById('searchBox');
    const subjectGrid = document.getElementById('subjectGrid');
    const interviewGrid = document.getElementById('interviewGrid');
    const subjects = subjectGrid.querySelectorAll('.col');
    const interviews = interviewGrid.querySelectorAll('.col');

    searchBox.addEventListener('input', () => {
      const query = searchBox.value.toLowerCase();

      // filter subjects
      subjects.forEach(col => {
        const subjectName = col.getAttribute('data-subject').toLowerCase();
        col.style.display = subjectName.includes(query) ? '' : 'none';
      });

      // filter interviews
      interviews.forEach(col => {
        const interviewName = col.getAttribute('data-interview').toLowerCase();
        col.style.display = interviewName.includes(query) ? '' : 'none';
      });
    });

    // Apply arrow hover to both subjects and interviews
    document.querySelectorAll(".subject-card, .interview-card").forEach(card => {
      const arrow = card.querySelector(".card-arrow");
      card.addEventListener("mouseenter", () => {
        arrow.innerHTML = "&#8593;"; // Up arrow
      });
      card.addEventListener("mouseleave", () => {
        arrow.innerHTML = "&#8595;"; // Down arrow
      });
    });
  </script>


  <?php include("footer.php"); ?>
</body>

</html>