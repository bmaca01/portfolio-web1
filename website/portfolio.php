<?php
$currentPage = 'portfolio';
$htmlTitle = 'benjmacaro.dev - projects';
$metaDescription = 'Browse BenjMacaro\'s collection of digital creations and coding projects';
$metaKeywords = 'web development portfolio, projects, coding, applications';
include 'includes/page-header.php';
?>

<a name="top"></a>
<!-- Quick Navigation -->
<table border="2" cellpadding="10" cellspacing="0" width="100%" bgcolor="#f0e6ff">
  <tr>
    <td>
      <font face="helvetica" size="2">
        <strong>Quick Navigation:</strong>
        <a href="#web-apps">Web Apps</a> |
        <a href="#compilers">Compilers & Interpreters</a> |
        <a href="#mini-projects">Mini Projects</a> |
        <a href="#languages">Programming Languages</a> |
        <a href="#frontend">Frontend</a> |
        <a href="#backend">Backend</a> |
        <a href="#devops">DevOps</a>
      </font>
    </td>
  </tr>
</table>
<br>

<h1 id="web-apps" class="section-heading">Web Apps</h1>
<hr>
<h2 class="section-heading">
    <a href="https://github.com/kairyvu/CS_490_Mediline_Backend">Mediline</a>
    </h2>
<p class="body-text">
    <strong>Type:</strong> Full Stack Application<br>
</p>
<p class="body-text">
    A role-based, multi-user medical appointment scheduling and management system.
</p>
<p class="body-text">
    Features:
    <ul>
        <li>Role-based access (patients, doctors, admins)</li>
        <li>Appointment booking and calendar view</li>
        <li>Patient records and history</li>
        <li>Notifications and reminders</li>
        <li>Real time chat between patients and doctors</li>
    </ul>
</p>
<p class="body-text">
    <strong>Tech Stack:</strong> React.js | Sass | Flask | FastAPI | MySQL
</p>

<br>

<h2 class="section-heading">
    <a href="#">Jira Clone<em style="font-size: 12pt"> (in progress)</em></a>
</h2>
<p class="body-text">
    <strong>Type:</strong> Full Stack Application<br>
</p>
<p class="body-text">
    A simplified clone of Jira for project management and issue tracking.
</p>
<p class="body-text">
    Features:
    <ul>
        <li>Single Sign On (SSO)</li>
        <li>Project and task management</li>
        <li>Kanban board view</li>
        <li>Drag-and-drop task organization</li>
        <li>Multi-user collaboration</li>
    </ul>
</p>
<p class="body-text">
    <strong>Tech Stack:</strong> Next.js | Tailwind CSS | Prisma ORM | PostgreSQL
</p>

<h2 class="section-heading">
    <a href="#">Personal Blog <em style="font-size: 10pt">(this site!)</em></a>
</h2>
<p class="body-text">
    <strong>Type:</strong> Full Stack Application, CMS<br>
</p>
<p class="body-text">
    A personal blogging platform with markdown support and a custom CMS.
</p>
<p class="body-text">
    Planned Features:
    <ul>
        <li>Markdown-based blog posts</li>
        <li>Custom CMS for post management</li>
        <li>Responsive design</li>
        <li>SEO optimization</li>
    </ul>
</p>
<p class="body-text">
    <strong>Tech Stack:</strong> HTML | CSS | PHP | MySQL 
</p>
<p class="body-text"><strong>Deployment:</strong> From my homelab server</p>

<p align="right"><font size="2"><a href="#top">↑ Back to Top</a></font></p>
<br>

<h1 id="compilers" class="section-heading">Formal languages, Interpreters, Compilers</h1>
<hr>
<h2 class="section-heading">
    <a href="https://github.com/bmaca01/dpda">DPDA Simulator</a>
</h2>
<em><a href="https://dpda.benjmacaro.dev">demo</a></em>
<p class="body-text">
    <strong>Type:</strong> Full Stack Application, Web API, CLI App<br>
</p>
<p class="body-text">
    A web-based Deterministic Pushdown Automata (DPDA) Simulator.<br>
    Core logic implemented in python with a FastAPI backend API and a Vue 3 frontend.<br>
    Originally written as a uni project, repurposed and expanded for public use.
</p>
<p class="body-text">
    Features:
    <ul>
        <li>State diagram visualization</li>
        <li>Custom DPDA creation and editing</li>
        <li>DPDA computation trace</li>
        <li>Input validation and error handling</li>
    </ul>
</p>
<p class="body-text">
    <strong>Tech Stack:</strong> Vue 3 | Tailwind CSS | Cytoscape.js | Python | FastAPI
</p>

<br>

<h2 class="section-heading">
    <a href="https://github.com/bmaca01/cool-typecheck">Type Checker</a>
</h2>
<p class="body-text">
    <strong>Type:</strong> Compiler Module<br>
</p>
<p class="body-text">
    A static type checker for <a href="https://theory.stanford.edu/~aiken/software/cool/cool-manual.pdf">Classroom Object-Oriented Language</a> (COOL), implemented in python.
    Part of a larger compiler project for a uni course.
</p>
<p class="body-text">
    Features:
    <ul>
        <li>Type inference and checking</li>
        <li>Class hierarchy and inheritance handling</li>
        <li>Error reporting with line numbers</li>
    </ul>
</p>
<p class="body-text">
    <strong>Language:</strong> Python
</p>
<p class="body-text"><strong>In progress:</strong> x86 code gen</p>

<br>

<h2 class="section-heading">
    <a href="https://github.com/bmaca01/cpp-interpreter">Pascal-like Language Interpreter</a>
</h2>
<p class="body-text">
    <strong>Type:</strong> CLI REPL / Interpreter<br>
</p>
<p class="body-text">
    An interpreter for a simplified Pascal-like programming language.
    Supports basic constructs like variables, arithmetic, and loops.
</p>
<p class="body-text">
    Features:
    <ul>
        <li>Lexical analysis and parsing</li>
        <li>Execution of statements and expressions</li>
        <li>Error handling and reporting</li>
    </ul>
</p>
<p class="body-text">
    <strong>Tech Stack:</strong> C++ | Make
</p>

<p align="right"><font size="2"><a href="#top">↑ Back to Top</a></font></p>
<br>

<h2 id="mini-projects" class="section-heading">Mini Projects & Experiments</h2>
<hr>
<table width="100%" cellpadding="15" cellspacing="0">
    <tr>
        <td width="33%" valign="top">
            <strong class="text-primary">
                <a href="https://github.com/bmaca01/OS_1000_lines">OS Kernel Development</a>
            </strong><br>
            <p class="text-primary">Refactored a minimal RISC-V operating system kernel from monolithic code into six focused
  modules (memory, I/O, filesystem, scheduler, trap handler). The kernel implements virtual memory, process isolation, VirtIO
  block device driver, and system call interface.</p>
        </td>
        <td width="33%" valign="top"> 
            <strong class="text-primary">
                <a href="#">reddish (Reddit Clone)</a>
            </strong><br>
            <p class="text-primary">A simple social media web app that implements multi-user authentication</p>
        </td>
        <td width="33%" valign="top"> 
            <strong class="text-primary">
                <a href="https://github.com/bmaca01/canvas-subscriber">Canvas subscriber client</a>
            </strong><br>
            <p class="text-primary">Python client implementing Canvas API to automatically download files from enrolled courses</p>
        </td>
    </tr>
</table>

<br>
<p align="right"><font size="2"><a href="#top">↑ Back to Top</a></font></p>

<h2 id="languages" class="section-heading">Programming Languages</h2>
<hr>
<table width="100%" cellpadding="10" cellspacing="0">
    <tr>
        <td width="50%" valign="top">
            <ul class="text-primary">
                <li>C</li>
                <li>C++</li>
                <li>Bash</li>
                <li>Java</li>
            </ul>
        </td>
        <td width="50%" valign="top">
            <ul class="text-primary">
                <li>JavaScript</li>
                <li>TypeScript</li>
                <li>PHP</li>
                <li>Python</li>
            </ul>
        </td>
    </tr>
    <tr>
        <td width="33%" valign="top">
            <ul class="text-primary">
                <li>Haskell</li>
                <li>Rust</li>
            </ul>
        </td>
        <td width="50%" valign="top">
            <ul class="text-primary">
                <li>RISC-V Assembly</li>
            </ul>
        </td>
    </tr>
</table>

<br>
<p align="right"><font size="2"><a href="#top">↑ Back to Top</a></font></p>

<h2 id="frontend" class="section-heading">Frontend Development</h2>
<hr>
<table width="100%" cellpadding="10" cellspacing="0">
    <tr>
        <td width="50%" valign="top">
            <strong class="text-primary">Frameworks & Libraries:</strong>
            <ul class="text-primary">
                <li>React.js</li>
                <li>Vue.js 3</li>
                <li>Next.js</li>
            </ul>
        </td>
        <td width="50%" valign="top">
            <strong class="text-primary">Styling & Design:</strong>
            <ul class="text-primary">
                <li>Tailwind CSS</li>
                <li>Material-UI</li>
            </ul>
        </td>
    </tr>
</table>

<br>

<h2 id="backend" class="section-heading">Backend Development</h2>
<hr>
<table width="100%" cellpadding="10" cellspacing="0">
    <tr>
        <td width="50%" valign="top">
            <strong class="text-primary">Frameworks & Runtime:</strong>
            <ul class="text-primary">
                <li>Node.js</li>
                <li>Flask</li>
                <li>FastAPI</li>
                <li>Laravel</li>
                <li>Nginx</li>
                <li>Apache</li>
                <li>RabbitMQ</li>
            </ul>
        </td>
        <td width="50%" valign="top">
            <strong class="text-primary">Databases & Storage:</strong>
            <ul class="text-primary">
                <li>PostgreSQL</li>
                <li>MongoDB</li>
                <li>Redis</li>
                <li>MySQL/MariaDB</li>
                <li>SQLite</li>
                <li>Oracle</li>
            </ul>
        </td>
    </tr>
</table>

<br>

<h2 id="devops" class="section-heading">DevOps & Tools</h2>
<hr>
<table width="100%" cellpadding="10" cellspacing="0">
    <tr>
        <td width="33%" valign="top">
            <strong class="text-primary">Version Control:</strong>
            <ul class="text-primary">
                <li>Git</li>
                <li>GitHub</li>
            </ul>
        </td>
        <td width="33%" valign="top">
            <strong class="text-primary">Cloud & CI/CD:</strong>
            <ul class="text-primary">
                <li>AWS (EC2, S3, Lambda)</li>
                <li>Docker</li>
                <li>CircleCI</li>
                <li>Pytest</li>
            </ul>
        </td>
    </tr>
</table>

<br>
<p align="right"><font size="2"><a href="#top">↑ Back to Top</a></font></p>

<?php include 'includes/page-footer.php'; ?>
