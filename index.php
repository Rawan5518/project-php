
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Adaptive Home-Based Learning Support</title>
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Special+Elite&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/styles.css" />
</head>

<body>
  <!-- NAVBAR -->
  <header class="nav">
    <div class="container nav__inner">
      <div class="brand">
        <div class="brand__logo">Logo</div>
        <div class="brand__name">Adaptive Home-Based Learning Support</div>
      </div>

      <nav class="nav__links">
        <a class="active" href="#">Home</a>
        <a href="#services">Services</a>
        <a href="#resources">Resources</a>
        <a href="#about">About Us</a>
        <a href="#contact">Contact Us</a>
        <a class="btn btn--dark" href="#contact">Add Button</a>
      </nav>
    </div>
  </header>

  <!-- HERO -->
  <section class="hero" aria-label="Hero">
    <div class="hero__bg"></div>
    <div class="hero__overlay"></div>

    <div class="container hero__content">
      <h1>Support Your Child’s Learning Journey</h1>
      <p>
        We provide a structured, adaptive support system to help children with learning
        difficulties through personalized activities, progress tracking, and parent guidance.
      </p>
      <div class="hero__actions">
        <a class="btn btn--primary" href="#contact">Contact Us</a>
        <a class="btn btn--ghost" href="#services">Explore Services</a>
        <a href="register.php" class="btn btn--primary">Create Account</a>
        <a class="btn btn--outline-light" href="login.php"> Log in </a>
        <a class="btn btn--primary" href="admin_login.php"> Admin </a>
      </div>
    </div>
  </section>

  <!-- MISSION -->
  <section class="section section--gold" id="about">
    <div class="container split">
      <div class="split__text">
        <h2>Our Mission</h2>
        <p>
          We aim to create an inclusive learning environment that adapts to each child’s needs.
          Our mission is to empower parents and help children overcome learning challenges
          with practical tools and targeted support.
        </p>
        
      </div>

      <div class="split__media">
        <div class="media-card">
          <img src="assets/study.webp"
     alt="Child with learning difficulties" />
        </div>
      </div>
    </div>
  </section>

  <!-- HOW IT WORKS -->
  <section class="section section--gold" id="services">
    <div class="container">
      <h2>How Our System Works</h2>
      <p class="lead">
        A simple 4-step process designed to support your child’s learning journey.
      </p>

      <div class="grid-2x2">
        <div class="card">
          <h3>Assessment</h3>
          <p>
            We start with a parental assessment to understand learning needs and challenges,
            so we can tailor support effectively.
          </p>
        </div>

        <div class="card">
          <h3>Diagnostic Testing</h3>
          <p>
            Adaptive diagnostic tests evaluate strengths and weaknesses to build a personalized
            learning path.
          </p>
        </div>

        <div class="card">
          <h3>Personalized Learning Path</h3>
          <p>
            Customized activities aligned with learning style—engaging, structured, and measurable.
          </p>
        </div>

        <div class="card">
          <h3>Progress Tracking</h3>
          <p>
            Analytics and reports track progress over time, helping parents adjust the plan when needed.
          </p>
        </div>
      </div>
    </div>
  </section>

  <!-- TESTIMONIALS -->
  <section class="section section--gold" id="resources">
    <div class="container">
      <h2>What Our Clients Say</h2>
      <p class="lead">
        Hear from parents and children who experienced the impact of our support system.
      </p>

      <div class="grid-3">
        <article class="tcard">
            <img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=1200&q=70" alt="Parent and child" />
          <div class="tcard__body">
            <h3>Parent Feedback</h3>
            <p>“My child is thriving, and I feel more empowered as a parent.”</p>
            
          </div>
        </article>

        <article class="tcard">
          <img src="https://images.unsplash.com/photo-1516627145497-ae6968895b74?auto=format&fit=crop&w=1200&q=70" alt="Kids playing with learning blocks" />
          <div class="tcard__body">
            <h3>Child’s Experience</h3>
            <p>“I love the games and activities. Learning is fun and I improved a lot.”</p>
            
          </div>
        </article>

        <article class="tcard">
          <img src="https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?auto=format&fit=crop&w=1200&q=70" alt="Student studying" />
          <div class="tcard__body">
            <h3>Success Stories</h3>
            <p>“He finally understood concepts he struggled with for years.”</p>
           
          </div>
        </article>
      </div>
    </div>
  </section>

  <!-- CONTACT -->
  <section class="section" id="contact">
    <div class="container contact">
      <h2>Contact Us</h2>
      <p class="lead">Send a message and we’ll respond within 24–48 hours.</p>
      <form class="form">
        <input placeholder="Full name" />
        <input placeholder="Email" type="email" />
        <textarea placeholder="How can we help?" rows="5"></textarea>
        <button class="btn btn--primary" type="submit">Send</button>
      </form>
    </div>
  </section>

  <footer class="footer">
    <div class="container">© 2025 Adaptive Home-Based Learning Support</div>
  </footer>
</body>
</html>