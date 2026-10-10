<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CourseCategory;
use App\Models\Course;
use App\Models\Section;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Support\Str;

class DemoCoursesSeeder extends Seeder
{
    public function run(): void
    {
        // Get super admin or instructor user as author
        $author = User::where('email', 'skillspot.in@gmail.com')->first() 
               ?? User::where('email', 'teacher@skillspot.in')->first() 
               ?? User::first();

        // ── 1. Create Course Categories ─────────────────────────────────────
        $categories = [
            [
                'name'        => 'Web Development',
                'slug'        => 'web-development',
                'icon'        => '🌐',
                'description' => 'Frontend, Backend, HTML, CSS, JavaScript, React & Full-Stack Development',
                'is_active'   => true,
                'order_col'   => 1,
            ],
            [
                'name'        => 'AI & Machine Learning',
                'slug'        => 'ai-machine-learning',
                'icon'        => '🤖',
                'description' => 'Artificial Intelligence, Deep Learning, Python ML & Generative AI',
                'is_active'   => true,
                'order_col'   => 2,
            ],
            [
                'name'        => 'Robotics & IoT',
                'slug'        => 'robotics-iot',
                'icon'        => '🦾',
                'description' => 'Hardware Automation, Arduino, Microcontrollers & Robotics Engineering',
                'is_active'   => true,
                'order_col'   => 3,
            ],
            [
                'name'        => 'Computer Science & Coding',
                'slug'        => 'computer-science-coding',
                'icon'        => '💻',
                'description' => 'Core Programming Fundamentals, Data Structures & Software Development',
                'is_active'   => true,
                'order_col'   => 4,
            ],
        ];

        foreach ($categories as $catData) {
            CourseCategory::updateOrCreate(['slug' => $catData['slug']], $catData);
        }

        // ── 2. Create Demo Courses ─────────────────────────────────────────

        // Course 1: Full-Stack Web Development Bootcamp
        $webCourse = Course::updateOrCreate(
            ['slug' => 'full-stack-web-development-bootcamp'],
            [
                'vendor_id'         => $author ? $author->id : null,
                'title'             => 'Full-Stack Web Development Bootcamp',
                'description'       => 'Master modern web development from absolute scratch! Build real-world responsive web applications using HTML5, CSS3, JavaScript ES6+, and React.',
                'category'          => 'Web Development',
                'level'             => 'beginner',
                'language'          => 'en',
                'price'             => 0,
                'sale_price'        => null,
                'is_free'           => true,
                'thumbnail'         => 'https://images.unsplash.com/photo-1593720213428-28a5b9e94613?auto=format&fit=crop&w=800&q=80',
                'intro_video'       => null,
                'requirements'      => json_encode(['Basic computer operating skills', 'No prior programming experience required']),
                'outcomes'          => json_encode(['Build responsive websites from scratch', 'Master HTML5, CSS3, JavaScript & modern frameworks', 'Deploy live web applications to cloud platforms']),
                'max_students'      => 0,
                'course_type'       => 'open',
                'is_featured'       => true,
                'visibility'        => 'public',
                'is_published'      => true,
                'registration_open' => true,
            ]
        );

        // Web Course Sections & Lessons
        $secWeb1 = Section::updateOrCreate(
            ['course_id' => $webCourse->id, 'title' => 'HTML5 & Web Foundations'],
            ['order' => 1]
        );

        Lesson::updateOrCreate(
            ['section_id' => $secWeb1->id, 'title' => 'Introduction to Web Development & HTML5 Structure'],
            [
                'type'        => 'video',
                'content'     => 'Welcome to Web Development! In this lesson we cover standard HTML5 document structure, semantic tags, and developer tools.',
                'duration'    => 900,
                'is_preview'  => true,
                'order'       => 1,
            ]
        );

        Lesson::updateOrCreate(
            ['section_id' => $secWeb1->id, 'title' => 'Working with HTML Elements, Forms, and Semantic Tags'],
            [
                'type'        => 'text',
                'content'     => 'HTML5 forms allow user input collection. Learn form elements: input, select, textarea, button, and validation attributes.',
                'duration'    => 600,
                'is_preview'  => true,
                'order'       => 2,
            ]
        );

        $secWeb2 = Section::updateOrCreate(
            ['course_id' => $webCourse->id, 'title' => 'Modern CSS3 & Responsive Styling'],
            ['order' => 2]
        );

        Lesson::updateOrCreate(
            ['section_id' => $secWeb2->id, 'title' => 'CSS Flexbox & Grid Layout Masterclass'],
            [
                'type'        => 'video',
                'content'     => 'Master modern layout systems in CSS. Flexbox handles 1D layout alignments, while CSS Grid manages complex 2D page layouts.',
                'duration'    => 1200,
                'is_preview'  => false,
                'order'       => 1,
            ]
        );

        Lesson::updateOrCreate(
            ['section_id' => $secWeb2->id, 'title' => 'Live Web App Development Q&A & Code-Along'],
            [
                'type'              => 'live',
                'description'       => 'Join instructor live to code a full responsive application and get real-time feedback on your code.',
                'live_platform'     => 'google_meet',
                'live_url'          => 'https://meet.google.com/abc-defg-hij',
                'live_scheduled_at' => now()->addDays(2)->setHour(18)->setMinute(0),
                'live_duration_min' => 60,
                'live_meeting_id'   => 'abc-defg-hij',
                'is_preview'        => true,
                'order'             => 2,
            ]
        );


        // Course 2: Artificial Intelligence & Python ML Fundamentals
        $aiCourse = Course::updateOrCreate(
            ['slug' => 'ai-python-machine-learning-fundamentals'],
            [
                'vendor_id'         => $author ? $author->id : null,
                'title'             => 'Artificial Intelligence & Python ML Fundamentals',
                'description'       => 'Step into the future of tech. Learn Python for AI, neural networks, machine learning models, and how to build intelligent applications with Generative AI APIs.',
                'category'          => 'AI & Machine Learning',
                'level'             => 'beginner',
                'language'          => 'en',
                'price'             => 0,
                'sale_price'        => null,
                'is_free'           => true,
                'thumbnail'         => 'https://images.unsplash.com/photo-1677442136019-21780efad99a?auto=format&fit=crop&w=800&q=80',
                'intro_video'       => null,
                'requirements'      => json_encode(['Curiosity to learn AI', 'Basic math background']),
                'outcomes'          => json_encode(['Understand core Machine Learning concepts', 'Build predictive models with Python & Scikit-Learn', 'Integrate LLM & Prompt Engineering APIs into apps']),
                'max_students'      => 0,
                'course_type'       => 'open',
                'is_featured'       => true,
                'visibility'        => 'public',
                'is_published'      => true,
                'registration_open' => true,
            ]
        );

        $secAi1 = Section::updateOrCreate(
            ['course_id' => $aiCourse->id, 'title' => 'Introduction to AI & Data Science'],
            ['order' => 1]
        );

        Lesson::updateOrCreate(
            ['section_id' => $secAi1->id, 'title' => 'What is Artificial Intelligence? ML vs Deep Learning'],
            [
                'type'        => 'video',
                'content'     => 'Explore the foundations of AI, how algorithms learn from data, and real-world industrial AI applications.',
                'duration'    => 800,
                'is_preview'  => true,
                'order'       => 1,
            ]
        );

        Lesson::updateOrCreate(
            ['section_id' => $secAi1->id, 'title' => 'Python Environment Setup & NumPy/Pandas Essentials'],
            [
                'type'        => 'text',
                'content'     => 'Setup Jupyter Notebooks, VS Code, NumPy arrays, and Pandas DataFrames for data manipulation and analysis.',
                'duration'    => 700,
                'is_preview'  => true,
                'order'       => 2,
            ]
        );

        $secAi2 = Section::updateOrCreate(
            ['course_id' => $aiCourse->id, 'title' => 'Generative AI & LLMs Live Masterclass'],
            ['order' => 2]
        );

        Lesson::updateOrCreate(
            ['section_id' => $secAi2->id, 'title' => 'Building AI Apps with LLM & Prompt Engineering APIs'],
            [
                'type'              => 'live',
                'description'       => 'Live interactive session on prompt engineering, embeddings, vector databases, and building custom AI agents.',
                'live_platform'     => 'google_meet',
                'live_url'          => 'https://meet.google.com/xyz-uvwx-rst',
                'live_scheduled_at' => now()->addDays(4)->setHour(19)->setMinute(0),
                'live_duration_min' => 90,
                'live_meeting_id'   => 'xyz-uvwx-rst',
                'is_preview'        => true,
                'order'             => 1,
            ]
        );


        // Course 3: Robotics Engineering & Arduino Hardware Automation
        $roboticsCourse = Course::updateOrCreate(
            ['slug' => 'robotics-engineering-arduino-hardware-automation'],
            [
                'vendor_id'         => $author ? $author->id : null,
                'title'             => 'Robotics Engineering & Arduino Hardware Automation',
                'description'       => 'Learn how to build autonomous robots and IoT hardware devices! Master Arduino programming, sensors, motor drivers, circuit wiring, and wireless robotics control.',
                'category'          => 'Robotics & IoT',
                'level'             => 'intermediate',
                'language'          => 'en',
                'price'             => 0,
                'sale_price'        => null,
                'is_free'           => true,
                'thumbnail'         => 'https://images.unsplash.com/photo-1485827404703-89b55fcc595e?auto=format&fit=crop&w=800&q=80',
                'intro_video'       => null,
                'requirements'      => json_encode(['Interest in hardware & robotics', 'Basic physics concepts']),
                'outcomes'          => json_encode(['Understand microcontroller architecture & C++ coding', 'Wire sensors, servos, and motor drivers safely', 'Design autonomous obstacle-avoiding & Bluetooth robots']),
                'max_students'      => 0,
                'course_type'       => 'open',
                'is_featured'       => true,
                'visibility'        => 'public',
                'is_published'      => true,
                'registration_open' => true,
            ]
        );

        $secRob1 = Section::updateOrCreate(
            ['course_id' => $roboticsCourse->id, 'title' => 'Robotics & Microcontroller Foundations'],
            ['order' => 1]
        );

        Lesson::updateOrCreate(
            ['section_id' => $secRob1->id, 'title' => 'Introduction to Arduino & Circuit Wiring'],
            [
                'type'        => 'video',
                'content'     => 'Understand microcontroller pinouts, digital vs analog I/O, breadboard wiring, and uploading code with Arduino IDE.',
                'duration'    => 1100,
                'is_preview'  => true,
                'order'       => 1,
            ]
        );

        $secRob2 = Section::updateOrCreate(
            ['course_id' => $roboticsCourse->id, 'title' => 'Autonomous Robot Construction Live Workshop'],
            ['order' => 2]
        );

        Lesson::updateOrCreate(
            ['section_id' => $secRob2->id, 'title' => 'Assembling & Testing Autonomous Robot Chassis'],
            [
                'type'              => 'live',
                'description'       => 'Live hardware demonstration: wiring L298N motor drivers, ultrasonic distance sensors, and programming obstacle avoidance logic.',
                'live_platform'     => 'google_meet',
                'live_url'          => 'https://meet.google.com/rob-otic-lms',
                'live_scheduled_at' => now()->addDays(5)->setHour(17)->setMinute(30),
                'live_duration_min' => 60,
                'live_meeting_id'   => 'rob-otic-lms',
                'is_preview'        => true,
                'order'             => 1,
            ]
        );


        // Course 4: Computer Programming & Problem Solving for Beginners
        $codingCourse = Course::updateOrCreate(
            ['slug' => 'computer-programming-problem-solving-beginners'],
            [
                'vendor_id'         => $author ? $author->id : null,
                'title'             => 'Computer Programming & Problem Solving for Beginners',
                'description'       => 'The perfect starting point for any beginner. Learn fundamental coding logic, variables, algorithms, flowcharts, and object-oriented programming concepts.',
                'category'          => 'Computer Science & Coding',
                'level'             => 'beginner',
                'language'          => 'en',
                'price'             => 0,
                'sale_price'        => null,
                'is_free'           => true,
                'thumbnail'         => 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?auto=format&fit=crop&w=800&q=80',
                'intro_video'       => null,
                'requirements'      => json_encode(['No prior experience required', 'Desktop computer or laptop']),
                'outcomes'          => json_encode(['Think like a computer programmer', 'Master core algorithmic logic & flow control', 'Write clean, efficient code in Python & JavaScript']),
                'max_students'      => 0,
                'course_type'       => 'open',
                'is_featured'       => true,
                'visibility'        => 'public',
                'is_published'      => true,
                'registration_open' => true,
            ]
        );

        $secCode1 = Section::updateOrCreate(
            ['course_id' => $codingCourse->id, 'title' => 'Programming Essentials & Logic'],
            ['order' => 1]
        );

        Lesson::updateOrCreate(
            ['section_id' => $secCode1->id, 'title' => 'How Computers Work & Writing Your First Program'],
            [
                'type'        => 'video',
                'content'     => 'Learn how CPU, memory, and code interact. Write your first Hello World program in Python.',
                'duration'    => 750,
                'is_preview'  => true,
                'order'       => 1,
            ]
        );

        Lesson::updateOrCreate(
            ['section_id' => $secCode1->id, 'title' => 'Loops, Conditions, and Functions Masterclass'],
            [
                'type'        => 'text',
                'content'     => 'Understand if/else branching, while loops, for loops, and writing modular functions.',
                'duration'    => 900,
                'is_preview'  => true,
                'order'       => 2,
            ]
        );
    }
}
