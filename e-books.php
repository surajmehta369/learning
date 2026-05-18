<?php
include("conn.php");
session_name('STUDENT_SESSION');
session_start();

?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Subjects & Interview Questions — Bootstrap Page</title>
  <link rel="icon" type="image/png" href="images/favicon.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/styles.css">
  <?php include("assets/header.php"); ?>

  <style>
    .highlight {
      background-color: #ffe680;
      padding: 2px 4px;
      border-radius: 3px;
      font-weight: bold;
    }

    .highlight {
      background: linear-gradient(120deg, #ffe680, #ffd54f);
    }

    body {
      margin: 0;
      padding: 0;
      font-family: Arial, sans-serif;
      background: #fff;
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
      min-height: 55vh;
      margin: 0;
      padding: 60px 20px;
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
    }

    .subject-card,
    .interview-card {
      border: none;
      box-shadow: 0 6px 18px rgba(0, 0, 0, 0.15);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      position: relative;
      overflow: hidden;
    }

    .subject-card:hover,
    .interview-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 12px 28px rgba(0, 0, 0, 0.2);
    }

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

    .card-arrow {
      font-size: 24px;
      color: black;
      display: inline-block;
      transform: rotate(-270deg);
      transition: transform 0.8s ease;
      margin-top: 12px;
    }

    .subject-card:hover .card-arrow,
    .interview-card:hover .card-arrow {
      transform: rotate(-270deg);
    }
  </style>
</head>

<body>

  <div class="hero">
    <h1><span class="highlight">Master the Classics</span></h1>

    <div class="search-box">
      <input type="text" id="searchBox" placeholder="Search here ...">
      <button>Search</button>
    </div>
  </div>

  <div class="container py-4">
    <h4 class="section-title">Handwritten-notes</h4>
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4" id="subjectGrid">

      <?php
      $notes = [
        "C-Language" => "10QuWjMAuFAXPiWWbIQY93klfW3Z4pzif",
        "Cloud Computing" => "10869gr-PM7NS044GctNgZRegkHkqdqCM",
        "DBMS" => "10Ub0dwjzJxftoOKGvTqVs2dd7MQEpADn",
        "Information Security" => "10JZVHAc4UI4Suc6PgPBbBSf4zzbIoDyZ",
        "Python" => "10opnJGoLz9nrTPxZKkKiAg2UbB3uQQ5E",
        "Software Engineering" => "10d5oy_eLP7j6QkUls-VwTYXN2d1rIUkW",
        "Web Technology" => "10gepUK-RFheeCz4c7v1SAAuYfoJAf0x8"
      ];

      foreach ($notes as $subject => $driveId):
        $driveLink = "https://drive.google.com/file/d/" . $driveId . "/view?usp=drivesdk";
      ?>
        <div class="col" data-subject="<?= htmlspecialchars($subject) ?>">
          <a href="<?= $driveLink ?>" target="_blank" class="text-decoration-none">
            <div class="card subject-card text-center h-100">
              <div class="card-body">
                <h5 class="card-title"><?= htmlspecialchars($subject) ?></h5>
                <p class="card-text">Free Notes | Click to view &nbsp; &nbsp;<span class="card-arrow">&#8595;</span></p>
              </div>
            </div>
          </a>
        </div>
      <?php endforeach; ?>

    </div>

    <h4 class="section-title">Interview Questions</h4>
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4" id="interviewGrid">
      <?php
      $interviews = [
        "PHP Laravel" => "php%20laravel.pdf",
        "Python" => "python.pdf",
        "Data Science" => "datascience.pdf",
        "Web Development" => "web%20development.pdf",
        "Javascript" => "javascript.pdf",
        "JQuery" => "jquery.pdf",
        "React.js" => "React.pdf",
        "Node.js" => "Node.js.pdf",
        "GIT & GITHUB BASICS" => "Github.pdf"
      ];

      foreach ($interviews as $title => $file):
        $dataAttr = ($title === "GIT & GITHUB BASICS") ? "Github" : $title;
      ?>
        <div class="col" data-interview="<?= htmlspecialchars($dataAttr) ?>">
          <a href="e-books/<?= $file ?>" target="_blank" class="text-decoration-none">
            <div class="card interview-card text-center h-100">
              <div class="card-body">
                <h5 class="card-title"><?= htmlspecialchars($title) ?></h5>
                <p class="card-text">Free Questions | Click to view&nbsp; &nbsp;<span class="card-arrow">&#8595;</span></p>
              </div>
            </div>
          </a>
        </div>
      <?php endforeach; ?>
    </div>

  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const searchBox = document.getElementById('searchBox');
    const subjects = subjectGrid.querySelectorAll('.col');
    const interviews = interviewGrid.querySelectorAll('.col');

    function highlightText(element, query) {
      const title = element.querySelector('.card-title');

      // store original text
      if (!title.dataset.original) {
        title.dataset.original = title.textContent;
      }

      let text = title.dataset.original;

      if (query !== "") {
        const regex = new RegExp(`(${query})`, "gi");
        text = text.replace(regex, '<span class="highlight">$1</span>');
      }

      title.innerHTML = text;
    }

    searchBox.addEventListener('input', () => {

      const query = searchBox.value.toLowerCase();

      subjects.forEach(col => {
        const subjectName = col.getAttribute('data-subject').toLowerCase();

        if (subjectName.includes(query)) {
          col.style.display = "";
          highlightText(col, query);
        } else {
          col.style.display = "none";
        }
      });

      interviews.forEach(col => {
        const interviewName = col.getAttribute('data-interview').toLowerCase();

        if (interviewName.includes(query)) {
          col.style.display = "";
          highlightText(col, query);
        } else {
          col.style.display = "none";
        }
      });

    });
  </script>


  <?php include("assets/footer.php"); ?>
</body>

</html>