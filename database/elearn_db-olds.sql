-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 24, 2025 at 07:33 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `elearn_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `ai_recommendations`
--

CREATE TABLE `ai_recommendations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `score` decimal(5,2) NOT NULL DEFAULT 0.00,
  `wrong_questions` longtext DEFAULT NULL,
  `ai_feedback` longtext DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ai_recommendations`
--

INSERT INTO `ai_recommendations` (`id`, `user_id`, `module_id`, `quiz_id`, `score`, `wrong_questions`, `ai_feedback`, `created_at`) VALUES
(113, 31, 22, 13, 7.00, '[{\"question_text\":\"2. Internet security relies heavily on which technology for protecting data?\",\"correct_answer_text\":\"Encryption\"},{\"question_text\":\"4. Which is a major challenge in cybersecurity?\",\"correct_answer_text\":\"Ever-evolving security risks\"},{\"question_text\":\"6. Identity management is concerned with what?\",\"correct_answer_text\":\"Users\\\\\' access levels\"},{\"question_text\":\"7. What is cybercrime?\",\"correct_answer_text\":\"Crime where a computer is the tool or target\"},{\"question_text\":\"8. Cyberbullying occurs between which group?\",\"correct_answer_text\":\"Two minors\"},{\"question_text\":\"10. Pharming redirects users to:\",\"correct_answer_text\":\"A fake website\"},{\"question_text\":\"11. Which malware displays unwanted advertisements?\",\"correct_answer_text\":\"Adware\"},{\"question_text\":\"12. Spyware is designed to:\",\"correct_answer_text\":\"Monitor a user secretly\"},{\"question_text\":\"15. What triggers a logic bomb?\",\"correct_answer_text\":\"A specific condition\"},{\"question_text\":\"16. Worms differ from viruses because worms:\",\"correct_answer_text\":\"Self-replicate without a host\"},{\"question_text\":\"17. Which hacking type is legal and used for security testing?\",\"correct_answer_text\":\"White-hat\"},{\"question_text\":\"18. Keyloggers record:\",\"correct_answer_text\":\"Every keystroke typed\"},{\"question_text\":\"20. Ransomware prevents you from using your computer unless you:\",\"correct_answer_text\":\"Pay a fee\"},{\"question_text\":\"21. A firewall is designed to:\",\"correct_answer_text\":\"Block unauthorized access\"},{\"question_text\":\"22. Antivirus software protects against:\",\"correct_answer_text\":\"Viruses, Trojans, worms\"},{\"question_text\":\"23. What does NAT (Network Address Translation) do?\",\"correct_answer_text\":\"Shields private devices from the internet\"},{\"question_text\":\"24. Why should operating systems be regularly updated?\",\"correct_answer_text\":\"To fix vulnerabilities\"},{\"question_text\":\"25. What is a drive-by download?\",\"correct_answer_text\":\"Automatic installation of malware when visiting a website\"}]', '<p>Dear Mark Aljerick De Castro from Section BSINFO-1A,</p>\n\n<p>Thank you for completing the final quiz for the \"Introduction to IT Computing\" module. Your participation is a valuable step in your learning journey, and reviewing your results is an excellent way to strengthen your understanding of these critical concepts.</p>\n\n<p>Let\'s go over the questions you answered incorrectly to clarify these important topics:</p>\n<ul>\n    <li><strong>Internet security relies heavily on which technology for protecting data?</strong> Encryption protects data by scrambling it, making it unreadable to unauthorized parties.</li>\n    <li><strong>Which is a major challenge in cybersecurity?</strong> Cybersecurity faces constant challenges from new and evolving threats.</li>\n    <li><strong>Identity management is concerned with what?</strong> Identity management governs who can access specific resources within a system.</li>\n    <li><strong>What is cybercrime?</strong> Cybercrime involves a computer as either the means or the target of a crime.</li>\n    <li><strong>Cyberbullying occurs between which group?</strong> Cyberbullying refers to online harassment between two minors.</li>\n    <li><strong>Pharming redirects users to:</strong> Pharming redirects users to fraudulent, fake websites.</li>\n    <li><strong>Which malware displays unwanted advertisements?</strong> Adware displays unwanted advertisements on your device.</li>\n    <li><strong>Spyware is designed to:</strong> Spyware secretly tracks and collects user activity data.</li>\n    <li><strong>What triggers a logic bomb?</strong> A logic bomb activates only when a predefined condition is met.</li>\n    <li><strong>Worms differ from viruses because worms:</strong> Worms self-replicate and spread independently, unlike viruses.</li>\n    <li><strong>Which hacking type is legal and used for security testing?</strong> White-hat hacking is legal and used for security testing and improvement.</li>\n    <li><strong>Keyloggers record:</strong> Keyloggers record every keystroke a user types.</li>\n    <li><strong>Ransomware prevents you from using your computer unless you:</strong> Ransomware demands a fee to restore access to your locked computer or files.</li>\n    <li><strong>A firewall is designed to:</strong> A firewall prevents unauthorized access to and from a network.</li>\n    <li><strong>Antivirus software protects against:</strong> Antivirus software protects against malware like viruses, Trojans, and worms.</li>\n    <li><strong>What does NAT (Network Address Translation) do?</strong> NAT hides private network IP addresses from direct internet exposure.</li>\n    <li><strong>Why should operating systems be regularly updated?</strong> OS updates are crucial for patching security vulnerabilities.</li>\n    <li><strong>What is a drive-by download?</strong> A drive-by download automatically installs malware when visiting a malicious website.</li>\n</ul>\n\n<p>To further solidify your understanding of these vital security topics, I recommend watching a short introductory video. This will help connect the concepts of threats and protective measures in a visual way.</p>\n<p>\n    <a href=\"https://www.youtube.com/watch?v=ooF74Fz7v-Q\">Cyber Security In 7 Minutes | Cyber Security For Beginners</a>\n</p>\n\n<p>Keep up the great work, Mark! Every step you take in reviewing and learning from your attempts brings you closer to mastering these essential IT concepts. I am here to support your learning journey.</p>', '2025-11-24 05:31:11'),
(118, 32, 22, 13, 7.00, '[{\"question_text\":\"What is the main purpose of Internet Security?\",\"correct_answer_text\":\"To protect data during online transactions\"},{\"question_text\":\"Internet security relies heavily on which technology for protecting data?\",\"correct_answer_text\":\"Encryption\"},{\"question_text\":\"Cybersecurity refers to the protection of which of the following?\",\"correct_answer_text\":\"Internet-connected systems\"},{\"question_text\":\"Which is a major challenge in cybersecurity?\",\"correct_answer_text\":\"Ever-evolving security risks\"},{\"question_text\":\"Which cybersecurity area focuses on protecting apps through updates and testing?\",\"correct_answer_text\":\"Application security\"},{\"question_text\":\"Identity management is concerned with what?\",\"correct_answer_text\":\"Users\\\\\' access levels\"},{\"question_text\":\"What is cybercrime?\",\"correct_answer_text\":\"Crime where a computer is the tool or target\"},{\"question_text\":\"Cyberbullying occurs between which group?\",\"correct_answer_text\":\"Two minors\"},{\"question_text\":\"Phishing messages typically attempt to:\",\"correct_answer_text\":\"Trick users into giving usernames and passwords\"},{\"question_text\":\"Pharming redirects users to:\",\"correct_answer_text\":\"A fake website\"},{\"question_text\":\"Which malware displays unwanted advertisements?\",\"correct_answer_text\":\"Adware\"},{\"question_text\":\"Spyware is designed to:\",\"correct_answer_text\":\"Monitor a user secretly\"},{\"question_text\":\"A computer virus requires what to spread?\",\"correct_answer_text\":\"A host file\"},{\"question_text\":\"The “I Love You” virus was primarily spread through:\",\"correct_answer_text\":\"Email attachments\"},{\"question_text\":\"What triggers a logic bomb?\",\"correct_answer_text\":\"A specific condition\"},{\"question_text\":\"Worms differ from viruses because worms:\",\"correct_answer_text\":\"Self-replicate without a host\"},{\"question_text\":\"Which hacking type is legal and used for security testing?\",\"correct_answer_text\":\"White-hat\"},{\"question_text\":\"Keyloggers record:\",\"correct_answer_text\":\"Every keystroke typed\"},{\"question_text\":\"A botnet is a network of:\",\"correct_answer_text\":\"Hacked computers controlled remotely\"},{\"question_text\":\"Ransomware prevents you from using your computer unless you:\",\"correct_answer_text\":\"Pay a fee\"},{\"question_text\":\"A firewall is designed to:\",\"correct_answer_text\":\"Block unauthorized access\"},{\"question_text\":\"Antivirus software protects against:\",\"correct_answer_text\":\"Viruses, Trojans, worms\"},{\"question_text\":\"What does NAT (Network Address Translation) do?\",\"correct_answer_text\":\"Shields private devices from the internet\"},{\"question_text\":\"Why should operating systems be regularly updated?\",\"correct_answer_text\":\"To fix vulnerabilities\"},{\"question_text\":\"What is a drive-by download?\",\"correct_answer_text\":\"Automatic installation of malware when visiting a website\"}]', '<p>Dear Vonn Annilov Cabajes from Section BSINFO-1A,</p>\n\n<p>Thank you for completing the final quiz for the \"Latest Module.\" While your score of 7/25 (28%) indicates areas where we can focus our learning, your commitment and participation are excellent starting points. Every attempt is a valuable part of the learning process!</p>\n\n<p>Let\'s review the questions you answered incorrectly to strengthen your understanding of these important concepts:</p>\n<ul>\n    <li><strong>1. Internet Security Purpose:</strong> Its main goal is to protect your data during online transactions.</li>\n    <li><strong>2. Data Protection Technology:</strong> Internet security relies heavily on Encryption to secure data.</li>\n    <li><strong>3. Cybersecurity Scope:</strong> Cybersecurity protects all Internet-connected systems.</li>\n    <li><strong>4. Cybersecurity Challenge:</strong> A major challenge is the constant emergence of ever-evolving security risks.</li>\n    <li><strong>5. Application Security Focus:</strong> This area protects apps through updates and testing.</li>\n    <li><strong>6. Identity Management:</strong> This ensures proper control over users\' access levels.</li>\n    <li><strong>7. Cybercrime Definition:</strong> Cybercrime is where a computer is either the tool or the target of a crime.</li>\n    <li><strong>8. Cyberbullying Context:</strong> Cyberbullying specifically occurs between two minors.</li>\n    <li><strong>9. Phishing Attempts:</strong> Phishing messages aim to trick users into giving usernames and passwords.</li>\n    <li><strong>10. Pharming Redirection:</strong> Pharming redirects users to a fake website.</li>\n    <li><strong>11. Adware Function:</strong> Adware is malware that displays unwanted advertisements.</li>\n    <li><strong>12. Spyware Purpose:</strong> Spyware is designed to secretly monitor a user.</li>\n    <li><strong>13. Virus Requirement:</strong> A computer virus needs a host file to spread.</li>\n    <li><strong>14. \"I Love You\" Virus Spread:</strong> This virus spread primarily through email attachments.</li>\n    <li><strong>15. Logic Bomb Trigger:</strong> A logic bomb is activated by a specific condition.</li>\n    <li><strong>16. Worms vs. Viruses:</strong> Worms self-replicate without needing a host file.</li>\n    <li><strong>17. Legal Hacking:</strong> White-hat hacking is legal and used for security testing.</li>\n    <li><strong>18. Keylogger Record:</strong> Keyloggers record every keystroke typed.</li>\n    <li><strong>19. Botnet Network:</strong> A botnet is a network of hacked computers controlled remotely.</li>\n    <li><strong>20. Ransomware Action:</strong> Ransomware prevents computer use unless you pay a fee.</li>\n    <li><strong>21. Firewall Purpose:</strong> A firewall is designed to block unauthorized access.</li>\n    <li><strong>22. Antivirus Protection:</strong> Antivirus software protects against viruses, Trojans, and worms.</li>\n    <li><strong>23. NAT Function:</strong> NAT (Network Address Translation) shields private devices from the internet.</li>\n    <li><strong>24. OS Updates Reason:</strong> Operating systems should be updated regularly to fix vulnerabilities.</li>\n    <li><strong>25. Drive-by Download:</strong> This is the automatic installation of malware when visiting a website.</li>\n</ul>\n\n<p>To further solidify your understanding of these topics, I recommend watching a brief introductory video on common cybersecurity threats. You can search for one using this link:</p>\n<p><a href=\"https://www.youtube.com/results?search_query=Common+Cybersecurity+Threats+Explained\">Common Cybersecurity Threats Explained</a></p>\n\n<p>Keep striving, Vonn Annilov! Your dedication to learning about internet security is incredibly valuable. With continued effort and review, you will master these crucial concepts. I am here to support your progress!</p>', '2025-11-24 06:26:13');

-- --------------------------------------------------------

--
-- Table structure for table `assessments`
--

CREATE TABLE `assessments` (
  `id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `due_date` datetime NOT NULL,
  `estimated_time` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `checkpoint_quizzes`
--

CREATE TABLE `checkpoint_quizzes` (
  `id` int(11) NOT NULL,
  `module_part_id` int(11) NOT NULL,
  `quiz_title` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `checkpoint_quizzes`
--

INSERT INTO `checkpoint_quizzes` (`id`, `module_part_id`, `quiz_title`, `created_at`) VALUES
(1, 57, 'Cybersecurity, Cybercrimes, Malware, and Computer Security', '2025-11-23 14:23:39');

-- --------------------------------------------------------

--
-- Table structure for table `checkpoint_quiz_questions`
--

CREATE TABLE `checkpoint_quiz_questions` (
  `id` int(11) NOT NULL,
  `checkpoint_quiz_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `option1` varchar(255) NOT NULL,
  `option2` varchar(255) NOT NULL,
  `option3` varchar(255) NOT NULL,
  `option4` varchar(255) NOT NULL,
  `correct_answer` int(11) NOT NULL,
  `question_order` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `checkpoint_quiz_questions`
--

INSERT INTO `checkpoint_quiz_questions` (`id`, `checkpoint_quiz_id`, `question_text`, `option1`, `option2`, `option3`, `option4`, `correct_answer`, `question_order`) VALUES
(1, 1, 'Cybersecurity focuses on protecting what type of systems?', 'Only hardware', 'Internet-connected systems including hardware, software, and data', 'Only government systems', 'Only personal computers', 2, 1),
(2, 1, 'Which modern cybersecurity guideline recommends continuous monitoring and real-time assessments?', 'ISO', 'DepEd', 'NIST', 'IEEE', 3, 2),
(3, 1, 'What cybercrime involves tricking users through emails that look legitimate to steal usernames and passwords?', 'Pharming', 'Phishing', 'Malware injection', 'Botnet attack', 2, 3),
(4, 1, 'What makes pharming different from phishing?', 'Pharming uses fake emails', 'Pharming redirects users to fake websites even when typing the correct URL', 'Pharming uses viruses', 'Pharming requires user permission', 2, 4),
(5, 1, 'What type of malware secretly records every keystroke entered on a computer?', 'Trojan', 'Spyware', 'Keylogger', 'Worm', 3, 5),
(6, 1, 'Which type of hacker is known as an “ethical hacker” and is hired to find vulnerabilities?', 'Black-hat', 'Script kiddie', 'White-hat', 'Gray-hat', 3, 6),
(7, 1, 'A self-replicating malicious program that travels across networks without needing a host file is called a:', 'Virus', 'Worm', 'Trojan', 'Adware', 2, 7),
(8, 1, 'What type of cybercrime is defined as obtaining someone’s personal information to impersonate them?', 'Fraud', 'Identity theft', 'Shill bidding', 'Clickjacking', 2, 8),
(9, 1, 'What hardware feature hides private network devices from the public internet by modifying IP addresses?', 'Firewall', 'Keylogger', 'NAT (Network Address Translation)', 'Adware blocker', 3, 9),
(10, 1, 'What is considered the most important piece of security software that must always be updated?', 'Browser', 'Download manager', 'Operating System', 'Media player', 3, 10);

-- --------------------------------------------------------

--
-- Table structure for table `checkpoint_quiz_results`
--

CREATE TABLE `checkpoint_quiz_results` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `checkpoint_quiz_id` int(11) NOT NULL,
  `module_part_id` int(11) NOT NULL,
  `score` int(11) NOT NULL,
  `total_questions` int(11) NOT NULL,
  `percentage` decimal(5,2) NOT NULL,
  `user_answers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`user_answers`)),
  `completion_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `checkpoint_quiz_results`
--

INSERT INTO `checkpoint_quiz_results` (`id`, `user_id`, `module_id`, `checkpoint_quiz_id`, `module_part_id`, `score`, `total_questions`, `percentage`, `user_answers`, `completion_date`) VALUES
(2, 31, 22, 1, 57, 2, 10, 20.00, '{\"1\":1,\"2\":2,\"3\":2,\"4\":1,\"5\":1,\"6\":1,\"7\":2,\"8\":1,\"9\":1,\"10\":1}', '2025-11-24 05:28:45'),
(3, 32, 22, 1, 57, 0, 10, 0.00, '{\"1\":1,\"2\":1,\"3\":1,\"4\":3,\"5\":4,\"6\":1,\"7\":3,\"8\":3,\"9\":1,\"10\":2}', '2025-11-24 06:25:23');

-- --------------------------------------------------------

--
-- Table structure for table `daily_analytics`
--

CREATE TABLE `daily_analytics` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `total_study_time_seconds` int(11) DEFAULT 0,
  `total_focused_time_seconds` int(11) DEFAULT 0,
  `total_unfocused_time_seconds` int(11) DEFAULT 0,
  `session_count` int(11) DEFAULT 0,
  `average_focus_percentage` decimal(5,2) DEFAULT 0.00,
  `longest_session_seconds` int(11) DEFAULT 0,
  `modules_studied` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `daily_analytics`
--

INSERT INTO `daily_analytics` (`id`, `user_id`, `date`, `total_study_time_seconds`, `total_focused_time_seconds`, `total_unfocused_time_seconds`, `session_count`, `average_focus_percentage`, `longest_session_seconds`, `modules_studied`, `created_at`, `updated_at`) VALUES
(36, 8, '2025-07-20', 3600, 3240, 360, 1, 90.00, 3600, 1, '2025-07-24 15:49:24', '2025-07-24 15:49:24'),
(37, 8, '2025-07-21', 1800, 1620, 180, 1, 90.00, 1800, 1, '2025-07-24 15:49:24', '2025-07-24 15:49:24'),
(38, 8, '2025-07-22', 3600, 3240, 360, 1, 90.00, 3600, 1, '2025-07-24 15:49:24', '2025-07-24 15:49:24'),
(39, 8, '2025-07-23', 3600, 3240, 360, 1, 90.00, 3600, 1, '2025-07-24 15:49:24', '2025-07-24 15:49:24'),
(40, 8, '2025-07-24', 3600, 3240, 360, 1, 90.00, 3600, 1, '2025-07-24 15:49:24', '2025-07-24 15:49:24'),
(41, 9, '2025-07-21', 900, 360, 540, 1, 40.00, 900, 1, '2025-07-24 15:49:24', '2025-07-24 15:49:24'),
(42, 9, '2025-07-23', 900, 360, 540, 1, 40.00, 900, 1, '2025-07-24 15:49:24', '2025-07-24 15:49:24'),
(43, 9, '2025-07-24', 900, 360, 540, 1, 40.00, 900, 1, '2025-07-24 15:49:24', '2025-07-24 15:49:24'),
(44, 10, '2025-07-19', 3600, 2880, 720, 1, 80.00, 3600, 1, '2025-07-24 15:49:24', '2025-07-24 15:49:24'),
(45, 10, '2025-07-22', 3600, 2520, 1080, 1, 70.00, 3600, 1, '2025-07-24 15:49:24', '2025-07-24 15:49:24'),
(46, 10, '2025-07-24', 3600, 1800, 1800, 1, 50.00, 3600, 1, '2025-07-24 15:49:24', '2025-07-24 15:49:24');

-- --------------------------------------------------------

--
-- Table structure for table `eye_tracking_analytics`
--

CREATE TABLE `eye_tracking_analytics` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `section_id` int(11) DEFAULT NULL,
  `date` date NOT NULL,
  `total_focus_time` int(11) DEFAULT 0,
  `session_count` int(11) DEFAULT 0,
  `average_session_time` int(11) DEFAULT 0,
  `max_continuous_time` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `total_focused_time` int(11) DEFAULT 0 COMMENT 'Total focused time in seconds',
  `total_unfocused_time` int(11) DEFAULT 0 COMMENT 'Total unfocused time in seconds',
  `focus_percentage` decimal(5,2) DEFAULT 0.00 COMMENT 'Percentage of time focused'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `eye_tracking_analytics`
--

INSERT INTO `eye_tracking_analytics` (`id`, `user_id`, `module_id`, `section_id`, `date`, `total_focus_time`, `session_count`, `average_session_time`, `max_continuous_time`, `created_at`, `updated_at`, `total_focused_time`, `total_unfocused_time`, `focus_percentage`) VALUES
(1, 1, 14, 0, '2025-07-19', 10, 2, 5, 0, '2025-07-19 18:06:57', '2025-07-19 18:08:26', 0, 0, 0.00),
(2, 1, 14, 32, '2025-07-19', 19, 3, 6, 0, '2025-07-19 18:56:20', '2025-07-19 19:37:55', 0, 0, 0.00),
(3, 1, 14, 33, '2025-07-19', 4, 1, 4, 0, '2025-07-19 18:56:50', '2025-07-19 18:56:50', 0, 0, 0.00),
(4, 1, 14, 31, '2025-07-20', 17, 1, 17, 0, '2025-07-20 16:27:37', '2025-07-20 16:27:37', 0, 0, 0.00),
(5, 1, 14, 31, '2025-07-21', 127, 10, 12, 0, '2025-07-21 01:46:01', '2025-07-21 02:29:29', 0, 0, 0.00),
(20, 10, 14, 31, '2025-07-24', 1207, 37, 32, 0, '2025-07-24 04:40:06', '2025-07-24 12:17:28', 4557, 1555, 377.55),
(21, 10, 14, 32, '0000-00-00', 24, 1, 24, 0, '2025-07-24 04:54:57', '2025-07-24 04:54:57', 17, 7, 70.00),
(24, 7, 14, 31, '0000-00-00', 1, 1, 1, 0, '2025-07-24 05:00:39', '2025-07-24 05:00:39', 1, 0, 100.00),
(27, 7, 14, 35, '0000-00-00', 11, 1, 11, 0, '2025-07-24 05:02:10', '2025-07-24 05:02:10', 2, 8, 18.00),
(30, 7, 14, 34, '0000-00-00', 26, 1, 26, 0, '2025-07-24 05:04:12', '2025-07-24 05:04:12', 1, 25, 3.00),
(41, 7, 16, 1, '0000-00-00', 1, 1, 1, 0, '2025-07-24 11:36:47', '2025-07-24 11:36:47', 1, 0, 100.00),
(42, 7, 14, 32, '0000-00-00', 22, 1, 22, 0, '2025-07-24 11:37:53', '2025-07-24 11:37:53', 22, 0, 100.00),
(46, 10, 14, 34, '0000-00-00', 22, 1, 22, 0, '2025-07-24 12:03:30', '2025-07-24 12:03:30', 9, 13, 40.00),
(49, 1, 1, 1, '2025-07-26', 47, 4, 11, 0, '2025-07-26 18:33:24', '2025-07-26 18:35:09', 0, 0, 0.00),
(50, 1, 14, 0, '2025-07-26', 44, 1, 44, 0, '2025-07-26 18:34:39', '2025-07-26 18:34:39', 0, 0, 0.00),
(51, 10, 14, NULL, '2025-08-01', 43, 1, 60, 60, '2025-07-31 16:27:09', '2025-07-31 16:27:09', 0, 0, 0.00),
(52, 9, 14, NULL, '2025-08-01', 33, 1, 60, 60, '2025-07-31 16:30:41', '2025-07-31 16:30:41', 0, 0, 0.00),
(53, 10, 14, NULL, '2025-08-02', 35, 1, 60, 60, '2025-08-02 15:19:40', '2025-08-02 15:19:40', 0, 0, 0.00),
(54, 9, 14, 32, '2025-08-02', 31, 1, 60, 60, '2025-08-02 15:29:47', '2025-08-02 15:29:47', 0, 0, 0.00),
(55, 7, 14, NULL, '2025-08-02', 29, 1, 60, 60, '2025-08-02 15:33:24', '2025-08-02 15:33:24', 0, 0, 0.00),
(56, 7, 14, NULL, '2025-08-02', 49, 1, 120, 120, '2025-08-02 15:34:24', '2025-08-02 15:34:24', 0, 0, 0.00),
(57, 9, 16, NULL, '2025-08-02', 38, 1, 60, 60, '2025-08-02 15:42:11', '2025-08-02 15:42:11', 0, 0, 0.00),
(58, 9, 16, 41, '2025-08-02', 36, 1, 60, 60, '2025-08-02 15:43:38', '2025-08-02 15:43:38', 0, 0, 0.00),
(59, 10, 16, NULL, '2025-08-02', 21, 1, 60, 60, '2025-08-02 15:45:43', '2025-08-02 15:45:43', 0, 0, 0.00),
(60, 10, 16, NULL, '2025-08-02', 53, 1, 120, 120, '2025-08-02 15:46:43', '2025-08-02 15:46:43', 0, 0, 0.00),
(61, 10, 14, NULL, '2025-08-03', 40, 1, 60, 60, '2025-08-02 16:11:15', '2025-08-02 16:11:15', 0, 0, 0.00),
(62, 9, 14, NULL, '2025-08-03', 32, 1, 60, 60, '2025-08-02 16:14:38', '2025-08-02 16:14:38', 0, 0, 0.00),
(63, 9, 14, NULL, '2025-08-03', 81, 1, 120, 120, '2025-08-02 16:15:38', '2025-08-02 16:15:38', 0, 0, 0.00),
(64, 9, 14, NULL, '2025-08-03', 111, 1, 180, 180, '2025-08-02 16:16:38', '2025-08-02 16:16:38', 0, 0, 0.00),
(65, 9, 14, NULL, '2025-08-03', 128, 1, 240, 240, '2025-08-02 16:17:38', '2025-08-02 16:17:38', 0, 0, 0.00),
(66, 9, 14, NULL, '2025-08-04', 38, 1, 60, 60, '2025-08-03 16:53:11', '2025-08-03 16:53:11', 0, 0, 0.00),
(67, 7, 14, NULL, '2025-08-04', 40, 1, 60, 60, '2025-08-03 17:15:41', '2025-08-03 17:15:41', 0, 0, 0.00),
(68, 8, 14, NULL, '2025-08-04', 27, 1, 60, 60, '2025-08-03 17:46:57', '2025-08-03 17:46:57', 0, 0, 0.00),
(69, 8, 14, NULL, '2025-08-04', 29, 1, 120, 120, '2025-08-03 17:47:57', '2025-08-03 17:47:57', 0, 0, 0.00),
(70, 8, 14, 32, '2025-08-05', 12, 1, 60, 60, '2025-08-05 07:29:25', '2025-08-05 07:29:25', 0, 0, 0.00),
(71, 8, 14, NULL, '2025-08-05', 4, 1, 60, 60, '2025-08-05 07:44:12', '2025-08-05 07:44:12', 0, 0, 0.00),
(72, 7, 14, 35, '2025-08-05', 45, 1, 60, 60, '2025-08-05 07:48:28', '2025-08-05 07:48:28', 0, 0, 0.00),
(73, 1, 14, 38, '2025-08-08', 38, 1, 60, 60, '2025-08-08 04:40:21', '2025-08-08 04:40:21', 0, 0, 0.00),
(74, 1, 14, NULL, '2025-08-08', 49, 1, 60, 60, '2025-08-08 04:53:02', '2025-08-08 04:53:02', 0, 0, 0.00),
(75, 12, 17, NULL, '2025-08-29', 28, 1, 60, 60, '2025-08-29 13:02:00', '2025-08-29 13:02:00', 0, 0, 0.00),
(76, 13, 14, NULL, '2025-09-12', 24, 1, 60, 60, '2025-09-12 12:16:02', '2025-09-12 12:16:02', 0, 0, 0.00),
(77, 13, 14, NULL, '2025-09-12', 36, 1, 120, 120, '2025-09-12 12:17:02', '2025-09-12 12:17:02', 0, 0, 0.00),
(78, 14, 14, 32, '2025-09-19', 19, 1, 60, 60, '2025-09-19 14:42:33', '2025-09-19 14:42:33', 0, 0, 0.00),
(79, 14, 14, 35, '2025-09-19', 47, 2, 60, 60, '2025-09-19 14:44:55', '2025-09-19 14:47:44', 0, 0, 0.00),
(81, 14, 14, 38, '2025-09-19', 712, 21, 160, 420, '2025-09-19 14:51:07', '2025-09-19 15:17:41', 0, 0, 0.00),
(102, 15, 14, NULL, '2025-09-20', 37, 1, 60, 60, '2025-09-19 16:10:39', '2025-09-19 16:10:39', 0, 0, 0.00),
(103, 15, 14, NULL, '2025-09-20', 2, 1, 60, 60, '2025-09-19 16:22:00', '2025-09-19 16:22:00', 0, 0, 0.00),
(104, 16, 19, NULL, '2025-10-16', 12, 1, 60, 60, '2025-10-16 14:18:10', '2025-10-16 14:18:10', 0, 0, 0.00),
(105, 16, 19, 48, '2025-10-16', 45, 1, 60, 60, '2025-10-16 14:19:31', '2025-10-16 14:19:31', 0, 0, 0.00),
(178, 19, 19, NULL, '2025-10-28', 32, 1, 60, 60, '2025-10-28 05:36:38', '2025-10-28 05:36:38', 0, 0, 0.00),
(179, 19, 19, NULL, '2025-10-28', 70, 1, 120, 120, '2025-10-28 05:37:38', '2025-10-28 05:37:38', 0, 0, 0.00),
(180, 20, 19, NULL, '2025-10-28', 24, 1, 60, 60, '2025-10-28 05:54:22', '2025-10-28 05:54:22', 0, 0, 0.00),
(181, 20, 19, NULL, '2025-10-28', 67, 1, 120, 120, '2025-10-28 05:55:22', '2025-10-28 05:55:22', 0, 0, 0.00),
(182, 20, 19, NULL, '2025-10-28', 121, 1, 180, 180, '2025-10-28 05:56:22', '2025-10-28 05:56:22', 0, 0, 0.00),
(183, 20, 19, NULL, '2025-10-28', 155, 1, 220, 220, '2025-10-28 05:57:22', '2025-10-28 05:57:22', 0, 0, 0.00),
(184, 20, 19, NULL, '2025-10-28', 215, 1, 280, 280, '2025-10-28 05:58:22', '2025-10-28 05:58:22', 0, 0, 0.00),
(185, 20, 19, NULL, '2025-10-28', 292, 1, 360, 360, '2025-10-28 05:59:22', '2025-10-28 05:59:22', 0, 0, 0.00),
(186, 20, 19, NULL, '2025-10-28', 31, 1, 60, 60, '2025-10-28 06:06:41', '2025-10-28 06:06:41', 0, 0, 0.00),
(187, 20, 19, NULL, '2025-10-28', 4, 1, 60, 60, '2025-10-28 06:26:04', '2025-10-28 06:26:04', 0, 0, 0.00),
(188, 20, 19, NULL, '2025-10-28', 9, 1, 60, 60, '2025-10-28 06:42:35', '2025-10-28 06:42:35', 0, 0, 0.00),
(189, 20, 19, NULL, '2025-10-28', 16, 1, 120, 120, '2025-10-28 06:43:35', '2025-10-28 06:43:35', 0, 0, 0.00),
(190, 20, 19, NULL, '2025-10-28', 22, 1, 60, 60, '2025-10-28 06:56:26', '2025-10-28 06:56:26', 0, 0, 0.00),
(191, 20, 19, NULL, '2025-10-28', 6, 1, 60, 60, '2025-10-28 15:24:33', '2025-10-28 15:24:33', 0, 0, 0.00),
(192, 20, 19, NULL, '2025-10-28', 18, 1, 120, 120, '2025-10-28 15:25:33', '2025-10-28 15:25:33', 0, 0, 0.00),
(193, 20, 19, NULL, '2025-10-28', 18, 1, 151, 151, '2025-10-28 15:26:33', '2025-10-28 15:26:33', 0, 0, 0.00),
(194, 20, 19, NULL, '2025-10-28', 18, 1, 211, 211, '2025-10-28 15:27:33', '2025-10-28 15:27:33', 0, 0, 0.00),
(195, 20, 19, NULL, '2025-10-28', 18, 1, 271, 271, '2025-10-28 15:28:34', '2025-10-28 15:28:34', 0, 0, 0.00),
(196, 20, 19, NULL, '2025-10-28', 4, 1, 60, 60, '2025-10-28 15:30:37', '2025-10-28 15:30:37', 0, 0, 0.00),
(197, 20, 19, NULL, '2025-10-28', 28, 1, 120, 120, '2025-10-28 15:31:37', '2025-10-28 15:31:37', 0, 0, 0.00),
(198, 20, 19, NULL, '2025-10-28', 51, 1, 180, 180, '2025-10-28 15:32:37', '2025-10-28 15:32:37', 0, 0, 0.00),
(199, 20, 19, NULL, '2025-10-28', 70, 1, 212, 212, '2025-10-28 15:33:37', '2025-10-28 15:33:37', 0, 0, 0.00),
(200, 20, 19, NULL, '2025-10-28', 70, 1, 267, 267, '2025-10-28 15:34:37', '2025-10-28 15:34:37', 0, 0, 0.00),
(201, 20, 19, NULL, '2025-10-28', 1, 1, 60, 60, '2025-10-28 15:36:47', '2025-10-28 15:36:47', 0, 0, 0.00),
(202, 20, 19, NULL, '2025-10-28', 8, 1, 112, 112, '2025-10-28 15:37:46', '2025-10-28 15:37:46', 0, 0, 0.00),
(203, 20, 19, NULL, '2025-10-28', 8, 1, 138, 138, '2025-10-28 15:38:46', '2025-10-28 15:38:46', 0, 0, 0.00),
(204, 20, 19, NULL, '2025-10-28', 8, 1, 198, 198, '2025-10-28 15:39:46', '2025-10-28 15:39:46', 0, 0, 0.00),
(205, 20, 19, NULL, '2025-10-28', 8, 1, 258, 258, '2025-10-28 15:40:46', '2025-10-28 15:40:46', 0, 0, 0.00),
(206, 20, 19, NULL, '2025-10-28', 8, 1, 318, 318, '2025-10-28 15:41:46', '2025-10-28 15:41:46', 0, 0, 0.00),
(207, 20, 19, NULL, '2025-10-28', 8, 1, 438, 438, '2025-10-28 15:43:04', '2025-10-28 15:43:04', 0, 0, 0.00),
(208, 21, 19, NULL, '2025-11-11', 41, 1, 60, 60, '2025-11-11 14:00:24', '2025-11-11 14:00:24', 0, 0, 0.00),
(209, 21, 20, NULL, '2025-11-11', 6, 1, 60, 60, '2025-11-11 14:59:20', '2025-11-11 14:59:20', 0, 0, 0.00),
(210, 21, 20, NULL, '2025-11-11', 16, 1, 60, 60, '2025-11-11 15:06:03', '2025-11-11 15:06:03', 0, 0, 0.00),
(211, 22, 19, NULL, '2025-11-11', 3, 1, 60, 60, '2025-11-11 15:47:10', '2025-11-11 15:47:10', 0, 0, 0.00),
(212, 22, 20, NULL, '2025-11-11', 0, 1, 60, 60, '2025-11-11 15:49:33', '2025-11-11 15:49:33', 0, 0, 0.00),
(213, 21, 19, 49, '2025-11-12', 6, 4, 89, 106, '2025-11-11 16:08:33', '2025-11-11 16:12:32', 0, 0, 0.00),
(217, 22, 19, 49, '2025-11-12', 0, 3, 114, 152, '2025-11-11 16:16:46', '2025-11-11 16:18:46', 0, 0, 0.00),
(220, 23, 19, NULL, '2025-11-12', 22, 1, 60, 60, '2025-11-11 16:21:14', '2025-11-11 16:21:14', 0, 0, 0.00),
(221, 23, 19, NULL, '2025-11-12', 6, 1, 61, 61, '2025-11-11 16:25:06', '2025-11-11 16:25:06', 0, 0, 0.00),
(222, 24, 19, NULL, '2025-11-12', 19, 1, 60, 60, '2025-11-12 14:39:52', '2025-11-12 14:39:52', 0, 0, 0.00),
(223, 24, 19, NULL, '2025-11-12', 58, 1, 60, 60, '2025-11-12 14:44:12', '2025-11-12 14:44:12', 0, 0, 0.00),
(224, 24, 19, NULL, '2025-11-12', 29, 1, 60, 60, '2025-11-12 14:49:00', '2025-11-12 14:49:00', 0, 0, 0.00),
(225, 24, 19, NULL, '2025-11-12', 73, 1, 120, 120, '2025-11-12 14:50:00', '2025-11-12 14:50:00', 0, 0, 0.00),
(226, 24, 19, NULL, '2025-11-12', 95, 1, 180, 180, '2025-11-12 14:51:00', '2025-11-12 14:51:00', 0, 0, 0.00),
(227, 24, 19, NULL, '2025-11-12', 126, 1, 237, 237, '2025-11-12 14:52:00', '2025-11-12 14:52:00', 0, 0, 0.00),
(228, 24, 19, NULL, '2025-11-12', 144, 1, 255, 255, '2025-11-12 14:53:00', '2025-11-12 14:53:00', 0, 0, 0.00),
(229, 24, 19, NULL, '2025-11-12', 47, 1, 60, 60, '2025-11-12 15:01:04', '2025-11-12 15:01:04', 0, 0, 0.00),
(230, 24, 19, NULL, '2025-11-12', 88, 1, 120, 120, '2025-11-12 15:02:04', '2025-11-12 15:02:04', 0, 0, 0.00),
(231, 24, 19, 48, '2025-11-12', 198, 4, 93, 120, '2025-11-12 15:03:10', '2025-11-12 15:10:08', 0, 0, 0.00),
(235, 25, 20, 63, '2025-11-12', 157, 2, 90, 119, '2025-11-12 15:24:16', '2025-11-12 15:25:16', 0, 0, 0.00),
(237, 25, 20, NULL, '2025-11-12', 17, 1, 60, 60, '2025-11-12 15:31:07', '2025-11-12 15:31:07', 0, 0, 0.00),
(238, 25, 20, NULL, '2025-11-13', 45, 1, 60, 60, '2025-11-12 16:32:37', '2025-11-12 16:32:37', 0, 0, 0.00),
(239, 25, 20, NULL, '2025-11-13', 80, 1, 120, 120, '2025-11-12 16:33:37', '2025-11-12 16:33:37', 0, 0, 0.00),
(240, 25, 20, NULL, '2025-11-13', 107, 1, 180, 180, '2025-11-12 16:34:37', '2025-11-12 16:34:37', 0, 0, 0.00),
(241, 25, 20, NULL, '2025-11-13', 129, 1, 240, 240, '2025-11-12 16:35:36', '2025-11-12 16:35:36', 0, 0, 0.00),
(242, 25, 20, NULL, '2025-11-13', 145, 1, 300, 300, '2025-11-12 16:36:36', '2025-11-12 16:36:36', 0, 0, 0.00),
(243, 25, 20, NULL, '2025-11-13', 24, 1, 60, 60, '2025-11-12 16:38:21', '2025-11-12 16:38:21', 0, 0, 0.00),
(244, 21, 19, 50, '2025-11-13', 11, 1, 60, 60, '2025-11-12 16:59:14', '2025-11-12 16:59:14', 0, 0, 0.00),
(245, 25, 20, NULL, '2025-11-13', 47, 1, 60, 60, '2025-11-12 17:09:32', '2025-11-12 17:09:32', 0, 0, 0.00),
(246, 25, 20, NULL, '2025-11-13', 92, 1, 120, 120, '2025-11-12 17:10:32', '2025-11-12 17:10:32', 0, 0, 0.00),
(247, 25, 20, NULL, '2025-11-13', 44, 1, 60, 60, '2025-11-12 17:12:45', '2025-11-12 17:12:45', 0, 0, 0.00),
(248, 25, 20, NULL, '2025-11-13', 75, 1, 120, 120, '2025-11-12 17:13:45', '2025-11-12 17:13:45', 0, 0, 0.00),
(249, 25, 20, NULL, '2025-11-13', 119, 1, 180, 180, '2025-11-12 17:14:45', '2025-11-12 17:14:45', 0, 0, 0.00),
(250, 25, 20, NULL, '2025-11-13', 133, 1, 210, 210, '2025-11-12 17:15:45', '2025-11-12 17:15:45', 0, 0, 0.00),
(251, 25, 20, NULL, '2025-11-13', 33, 1, 60, 60, '2025-11-12 17:18:11', '2025-11-12 17:18:11', 0, 0, 0.00),
(252, 25, 20, NULL, '2025-11-13', 37, 1, 64, 64, '2025-11-12 17:19:11', '2025-11-12 17:19:11', 0, 0, 0.00),
(253, 25, 20, NULL, '2025-11-13', 106, 1, 180, 180, '2025-11-12 17:20:11', '2025-11-12 17:20:11', 0, 0, 0.00),
(254, 25, 20, NULL, '2025-11-13', 148, 1, 240, 240, '2025-11-12 17:21:11', '2025-11-12 17:21:11', 0, 0, 0.00),
(255, 25, 20, NULL, '2025-11-13', 176, 1, 300, 300, '2025-11-12 17:22:11', '2025-11-12 17:22:11', 0, 0, 0.00),
(256, 25, 20, NULL, '2025-11-13', 180, 1, 304, 304, '2025-11-12 17:23:11', '2025-11-12 17:23:11', 0, 0, 0.00),
(257, 26, 20, NULL, '2025-11-13', 42, 1, 60, 60, '2025-11-12 17:28:34', '2025-11-12 17:28:34', 0, 0, 0.00),
(258, 25, 20, NULL, '2025-11-13', 35, 1, 60, 60, '2025-11-12 17:32:38', '2025-11-12 17:32:38', 0, 0, 0.00),
(259, 25, 20, NULL, '2025-11-13', 87, 1, 120, 120, '2025-11-12 17:33:38', '2025-11-12 17:33:38', 0, 0, 0.00),
(260, 26, 20, NULL, '2025-11-13', 40, 1, 60, 60, '2025-11-12 17:48:28', '2025-11-12 17:48:28', 0, 0, 0.00),
(261, 27, 20, NULL, '2025-11-13', 44, 1, 60, 60, '2025-11-12 17:54:57', '2025-11-12 17:54:57', 0, 0, 0.00),
(262, 24, 19, NULL, '2025-11-13', 37, 1, 60, 60, '2025-11-12 18:00:06', '2025-11-12 18:00:06', 0, 0, 0.00),
(263, 21, 20, NULL, '2025-11-14', 39, 1, 60, 60, '2025-11-14 15:01:23', '2025-11-14 15:01:23', 0, 0, 0.00),
(264, 21, 20, NULL, '2025-11-15', 29, 1, 60, 60, '2025-11-15 15:56:42', '2025-11-15 15:56:42', 0, 0, 0.00),
(265, 27, 20, NULL, '2025-11-16', 11, 1, 60, 60, '2025-11-15 16:36:06', '2025-11-15 16:36:06', 0, 0, 0.00),
(266, 27, 20, NULL, '2025-11-16', 21, 1, 120, 120, '2025-11-15 16:37:06', '2025-11-15 16:37:06', 0, 0, 0.00),
(267, 27, 20, NULL, '2025-11-16', 15, 1, 60, 60, '2025-11-15 16:42:21', '2025-11-15 16:42:21', 0, 0, 0.00),
(268, 27, 20, NULL, '2025-11-16', 70, 1, 120, 120, '2025-11-15 16:43:21', '2025-11-15 16:43:21', 0, 0, 0.00),
(269, 26, 20, NULL, '2025-11-16', 33, 1, 60, 60, '2025-11-15 16:45:47', '2025-11-15 16:45:47', 0, 0, 0.00),
(270, 25, 20, NULL, '2025-11-17', 28, 1, 60, 60, '2025-11-17 13:07:37', '2025-11-17 13:07:37', 0, 0, 0.00),
(271, 27, 21, NULL, '2025-11-17', 31, 1, 60, 60, '2025-11-17 14:30:54', '2025-11-17 14:30:54', 0, 0, 0.00),
(272, 27, 21, NULL, '2025-11-17', 86, 1, 120, 120, '2025-11-17 14:31:53', '2025-11-17 14:31:53', 0, 0, 0.00),
(273, 27, 21, NULL, '2025-11-17', 137, 1, 180, 180, '2025-11-17 14:32:53', '2025-11-17 14:32:53', 0, 0, 0.00),
(274, 27, 21, NULL, '2025-11-17', 177, 1, 240, 240, '2025-11-17 14:33:54', '2025-11-17 14:33:54', 0, 0, 0.00),
(275, 27, 21, NULL, '2025-11-17', 222, 1, 300, 300, '2025-11-17 14:34:53', '2025-11-17 14:34:53', 0, 0, 0.00),
(276, 27, 21, NULL, '2025-11-17', 273, 1, 360, 360, '2025-11-17 14:35:53', '2025-11-17 14:35:53', 0, 0, 0.00),
(277, 27, 21, NULL, '2025-11-17', 319, 1, 420, 420, '2025-11-17 14:36:54', '2025-11-17 14:36:54', 0, 0, 0.00),
(278, 27, 21, NULL, '2025-11-17', 359, 1, 480, 480, '2025-11-17 14:37:54', '2025-11-17 14:37:54', 0, 0, 0.00),
(279, 27, 21, NULL, '2025-11-17', 436, 1, 562, 562, '2025-11-17 14:39:15', '2025-11-17 14:39:15', 0, 0, 0.00),
(280, 27, 21, NULL, '2025-11-17', 469, 1, 600, 600, '2025-11-17 14:39:53', '2025-11-17 14:39:53', 0, 0, 0.00),
(281, 28, 20, NULL, '2025-11-17', 22, 1, 60, 60, '2025-11-17 14:58:43', '2025-11-17 14:58:43', 0, 0, 0.00),
(282, 28, 20, NULL, '2025-11-17', 41, 1, 92, 92, '2025-11-17 14:59:43', '2025-11-17 14:59:43', 0, 0, 0.00),
(283, 28, 20, NULL, '2025-11-17', 102, 1, 152, 152, '2025-11-17 15:00:44', '2025-11-17 15:00:44', 0, 0, 0.00),
(284, 28, 20, NULL, '2025-11-17', 102, 1, 212, 212, '2025-11-17 15:01:43', '2025-11-17 15:01:43', 0, 0, 0.00),
(285, 26, 19, NULL, '2025-11-18', 45, 1, 60, 60, '2025-11-18 05:09:03', '2025-11-18 05:09:03', 0, 0, 0.00),
(286, 26, 19, NULL, '2025-11-18', 54, 1, 89, 89, '2025-11-18 05:10:03', '2025-11-18 05:10:03', 0, 0, 0.00),
(287, 21, 19, 50, '2025-11-18', 29, 1, 60, 60, '2025-11-18 05:13:18', '2025-11-18 05:13:18', 0, 0, 0.00),
(288, 29, 20, NULL, '2025-11-19', 51, 1, 60, 60, '2025-11-19 15:01:16', '2025-11-19 15:01:16', 0, 0, 0.00),
(289, 21, 19, 50, '2025-11-20', 47, 1, 60, 60, '2025-11-20 05:47:04', '2025-11-20 05:47:04', 0, 0, 0.00),
(290, 31, 22, NULL, '2025-11-23', 22, 1, 60, 60, '2025-11-23 14:32:34', '2025-11-23 14:32:34', 0, 0, 0.00),
(291, 31, 22, NULL, '2025-11-23', 62, 1, 120, 120, '2025-11-23 14:33:34', '2025-11-23 14:33:34', 0, 0, 0.00),
(292, 31, 22, NULL, '2025-11-23', 119, 1, 180, 180, '2025-11-23 14:34:34', '2025-11-23 14:34:34', 0, 0, 0.00),
(293, 31, 22, NULL, '2025-11-23', 58, 1, 60, 60, '2025-11-23 14:37:19', '2025-11-23 14:37:19', 0, 0, 0.00),
(294, 31, 22, NULL, '2025-11-23', 17, 1, 60, 60, '2025-11-23 14:43:35', '2025-11-23 14:43:35', 0, 0, 0.00),
(295, 31, 22, NULL, '2025-11-23', 17, 1, 91, 91, '2025-11-23 14:44:34', '2025-11-23 14:44:34', 0, 0, 0.00),
(296, 32, 22, NULL, '2025-11-24', 17, 1, 60, 60, '2025-11-24 06:24:37', '2025-11-24 06:24:37', 0, 0, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `eye_tracking_data`
--

CREATE TABLE `eye_tracking_data` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `focus_score` decimal(5,2) NOT NULL,
  `reading_speed` decimal(6,2) NOT NULL,
  `retention_rate` decimal(5,2) NOT NULL,
  `reread_frequency` int(11) NOT NULL,
  `focus_duration` int(11) NOT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eye_tracking_sessions`
--

CREATE TABLE `eye_tracking_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `section_id` int(11) DEFAULT NULL,
  `total_time_seconds` int(11) DEFAULT 0,
  `session_type` enum('viewing','pause','resume') DEFAULT 'viewing',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `focused_time_seconds` int(11) DEFAULT 0 COMMENT 'Time spent focused in seconds',
  `unfocused_time_seconds` int(11) DEFAULT 0 COMMENT 'Time spent unfocused in seconds',
  `session_data` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `eye_tracking_sessions`
--

INSERT INTO `eye_tracking_sessions` (`id`, `user_id`, `module_id`, `section_id`, `total_time_seconds`, `session_type`, `created_at`, `last_updated`, `focused_time_seconds`, `unfocused_time_seconds`, `session_data`) VALUES
(1, 7, 14, 0, 19, 'viewing', '2025-07-19 17:17:11', '2025-07-19 17:18:43', 0, 0, NULL),
(2, 7, 14, 32, 3, 'viewing', '2025-07-19 17:17:16', '2025-07-19 17:17:16', 0, 0, NULL),
(3, 7, 14, 31, 4, 'viewing', '2025-07-19 17:17:27', '2025-07-19 17:17:27', 0, 0, NULL),
(4, 1, 14, 0, 9, '', '2025-07-19 18:06:57', '2025-07-19 18:06:57', 0, 0, NULL),
(5, 1, 14, 0, 1, '', '2025-07-19 18:08:26', '2025-07-19 18:08:26', 0, 0, NULL),
(6, 1, 14, 32, 9, '', '2025-07-19 18:56:20', '2025-07-19 18:56:20', 0, 0, NULL),
(7, 1, 14, 33, 4, '', '2025-07-19 18:56:50', '2025-07-19 18:56:50', 0, 0, NULL),
(8, 1, 14, 32, 5, '', '2025-07-19 19:01:11', '2025-07-19 19:01:11', 0, 0, NULL),
(9, 1, 14, 32, 5, '', '2025-07-19 19:37:55', '2025-07-19 19:37:55', 0, 0, NULL),
(10, 1, 14, 31, 17, '', '2025-07-20 16:27:37', '2025-07-20 16:27:37', 0, 0, NULL),
(11, 1, 14, 31, 30, '', '2025-07-21 01:46:01', '2025-07-21 01:46:01', 0, 0, NULL),
(12, 1, 14, 31, 30, '', '2025-07-21 01:46:31', '2025-07-21 01:46:31', 0, 0, NULL),
(13, 1, 14, 31, 30, '', '2025-07-21 01:55:49', '2025-07-21 01:55:49', 0, 0, NULL),
(14, 1, 14, 31, 2, '', '2025-07-21 01:56:20', '2025-07-21 01:56:20', 0, 0, NULL),
(15, 1, 14, 31, 4, '', '2025-07-21 01:56:50', '2025-07-21 01:56:50', 0, 0, NULL),
(16, 1, 14, 31, 1, '', '2025-07-21 01:57:20', '2025-07-21 01:57:20', 0, 0, NULL),
(17, 1, 14, 31, 5, '', '2025-07-21 01:58:21', '2025-07-21 01:58:21', 0, 0, NULL),
(18, 1, 14, 31, 5, '', '2025-07-21 01:58:51', '2025-07-21 01:58:51', 0, 0, NULL),
(19, 1, 14, 31, 9, '', '2025-07-21 02:28:59', '2025-07-21 02:28:59', 0, 0, NULL),
(20, 1, 14, 31, 11, '', '2025-07-21 02:29:29', '2025-07-21 02:29:29', 0, 0, NULL),
(21, 1, 14, 31, 30, '', '2025-07-23 18:23:53', '2025-07-23 18:23:53', 20, 10, NULL),
(22, 1, 14, 32, 60, '', '2025-07-23 18:30:17', '2025-07-23 18:30:17', 45, 15, NULL),
(23, 1, 15, 33, 90, '', '2025-07-23 18:30:18', '2025-07-23 18:30:18', 60, 30, NULL),
(24, 1, 14, 34, 45, '', '2025-07-23 18:30:19', '2025-07-23 18:30:19', 30, 15, NULL),
(25, 7, 14, 31, 3, '', '2025-07-24 03:39:22', '2025-07-24 03:39:22', 0, 3, NULL),
(26, 7, 14, 31, 30, '', '2025-07-24 03:39:52', '2025-07-24 03:39:52', 27, 6, NULL),
(27, 7, 14, 31, 30, '', '2025-07-24 03:40:22', '2025-07-24 03:40:22', 55, 8, NULL),
(28, 7, 14, 31, 30, '', '2025-07-24 03:40:53', '2025-07-24 03:40:53', 79, 14, NULL),
(29, 7, 14, 31, 30, '', '2025-07-24 03:41:23', '2025-07-24 03:41:23', 109, 14, NULL),
(30, 7, 14, 31, 30, '', '2025-07-24 03:41:53', '2025-07-24 03:41:53', 134, 19, NULL),
(31, 7, 14, 31, 30, '', '2025-07-24 03:42:23', '2025-07-24 03:42:23', 161, 22, NULL),
(32, 7, 14, 31, 30, '', '2025-07-24 03:42:53', '2025-07-24 03:42:53', 190, 24, NULL),
(33, 7, 14, 31, 30, '', '2025-07-24 03:43:23', '2025-07-24 03:43:23', 212, 32, NULL),
(34, 7, 14, 31, 305, '', '2025-07-24 03:43:53', '2025-07-24 11:41:20', 661, 194, NULL),
(35, 4, 14, NULL, 166, 'viewing', '2025-07-19 04:09:27', '2025-07-24 04:09:27', 83, 83, NULL),
(36, 4, 14, NULL, 180, 'viewing', '2025-07-21 04:09:27', '2025-07-24 04:09:27', 134, 46, NULL),
(37, 4, 14, NULL, 147, 'viewing', '2025-07-20 04:09:27', '2025-07-24 04:09:27', 107, 40, NULL),
(38, 5, 14, NULL, 296, 'viewing', '2025-07-18 04:09:27', '2025-07-24 04:09:27', 215, 81, NULL),
(39, 5, 14, NULL, 259, 'viewing', '2025-07-17 04:09:27', '2025-07-24 04:09:27', 157, 102, NULL),
(40, 5, 14, NULL, 364, 'viewing', '2025-07-23 04:09:27', '2025-07-24 04:09:27', 257, 107, NULL),
(41, 5, 14, NULL, 189, 'viewing', '2025-07-23 04:09:27', '2025-07-24 04:09:27', 92, 97, NULL),
(42, 6, 14, NULL, 262, 'viewing', '2025-07-22 04:09:27', '2025-07-24 04:09:27', 162, 100, NULL),
(43, 6, 14, NULL, 172, 'viewing', '2025-07-21 04:09:27', '2025-07-24 04:09:27', 90, 82, NULL),
(44, 6, 14, NULL, 315, 'viewing', '2025-07-18 04:09:27', '2025-07-24 04:09:27', 224, 91, NULL),
(45, 8, 14, NULL, 167, 'viewing', '2025-07-21 04:09:27', '2025-07-24 04:09:27', 143, 24, NULL),
(46, 8, 14, NULL, 365, 'viewing', '2025-07-23 04:09:27', '2025-07-24 04:09:27', 296, 69, NULL),
(47, 8, 14, NULL, 249, 'viewing', '2025-07-21 04:09:27', '2025-07-24 04:09:27', 227, 22, NULL),
(48, 8, 14, NULL, 126, 'viewing', '2025-07-19 04:09:27', '2025-07-24 04:09:27', 87, 39, NULL),
(49, 9, 14, NULL, 386, 'viewing', '2025-07-18 04:09:27', '2025-07-24 04:09:27', 279, 107, NULL),
(50, 9, 14, NULL, 327, 'viewing', '2025-07-19 04:09:27', '2025-07-24 04:09:27', 234, 93, NULL),
(51, 10, 14, 31, 1157, '', '2025-07-24 04:43:31', '2025-07-24 12:17:28', 4517, 1545, NULL),
(52, 10, 14, 32, 84, '', '2025-07-24 04:54:57', '2025-07-24 04:55:57', 57, 104, NULL),
(53, 7, 14, 35, 71, '', '2025-07-24 05:02:10', '2025-07-24 05:03:10', 45, 76, NULL),
(54, 7, 14, 34, 86, '', '2025-07-24 05:04:12', '2025-07-24 05:05:12', 14, 153, NULL),
(55, 7, 16, 1, 1, '', '2025-07-24 11:36:47', '2025-07-24 11:36:47', 1, 0, NULL),
(56, 7, 14, 32, 43, '', '2025-07-24 11:37:53', '2025-07-24 11:39:03', 42, 0, NULL),
(57, 10, 14, 34, 82, '', '2025-07-24 12:03:30', '2025-07-24 12:04:30', 27, 129, NULL),
(58, 1, 1, 1, 27, '', '2025-07-26 18:33:24', '2025-07-26 18:33:24', 0, 0, NULL),
(59, 1, 1, 1, 18, '', '2025-07-26 18:33:54', '2025-07-26 18:33:54', 0, 0, NULL),
(60, 1, 14, 0, 44, '', '2025-07-26 18:34:39', '2025-07-26 18:34:39', 0, 0, NULL),
(61, 1, 1, 1, 1, '', '2025-07-26 18:35:09', '2025-07-26 18:35:09', 0, 0, NULL),
(62, 1, 1, 1, 1, '', '2025-07-26 18:35:09', '2025-07-26 18:35:09', 0, 0, NULL),
(63, 1, 1, 1, 1, '', '2025-07-26 18:35:09', '2025-07-26 18:35:09', 0, 0, NULL),
(64, 10, 14, NULL, 60, 'viewing', '2025-07-31 16:27:09', '2025-07-31 16:27:09', 0, 0, NULL),
(65, 9, 14, NULL, 60, 'viewing', '2025-07-31 16:30:41', '2025-07-31 16:30:41', 0, 0, NULL),
(66, 10, 14, NULL, 60, 'viewing', '2025-08-02 15:19:40', '2025-08-02 15:19:40', 0, 0, NULL),
(67, 9, 14, 32, 60, 'viewing', '2025-08-02 15:29:47', '2025-08-02 15:29:47', 0, 0, NULL),
(68, 7, 14, NULL, 60, 'viewing', '2025-08-02 15:33:24', '2025-08-02 15:33:24', 0, 0, NULL),
(69, 7, 14, NULL, 120, 'viewing', '2025-08-02 15:34:24', '2025-08-02 15:34:24', 0, 0, NULL),
(70, 9, 16, NULL, 60, 'viewing', '2025-08-02 15:42:11', '2025-08-02 15:42:11', 0, 0, NULL),
(71, 9, 16, 41, 60, 'viewing', '2025-08-02 15:43:38', '2025-08-02 15:43:38', 0, 0, NULL),
(72, 10, 16, NULL, 60, 'viewing', '2025-08-02 15:45:43', '2025-08-02 15:45:43', 0, 0, NULL),
(73, 10, 16, NULL, 120, 'viewing', '2025-08-02 15:46:43', '2025-08-02 15:46:43', 0, 0, NULL),
(74, 10, 14, NULL, 60, 'viewing', '2025-08-02 16:11:15', '2025-08-02 16:11:15', 0, 0, NULL),
(75, 9, 14, NULL, 60, 'viewing', '2025-08-02 16:14:38', '2025-08-02 16:14:38', 0, 0, NULL),
(76, 9, 14, NULL, 120, 'viewing', '2025-08-02 16:15:38', '2025-08-02 16:15:38', 0, 0, NULL),
(77, 9, 14, NULL, 180, 'viewing', '2025-08-02 16:16:38', '2025-08-02 16:16:38', 0, 0, NULL),
(78, 9, 14, NULL, 240, 'viewing', '2025-08-02 16:17:38', '2025-08-02 16:17:38', 0, 0, NULL),
(79, 9, 14, NULL, 60, 'viewing', '2025-08-03 16:53:11', '2025-08-03 16:53:11', 0, 0, NULL),
(80, 7, 14, NULL, 60, 'viewing', '2025-08-03 17:15:41', '2025-08-03 17:15:41', 0, 0, NULL),
(81, 2, 1, NULL, 1800, 'viewing', '2025-08-03 17:20:43', '2025-08-03 17:20:43', 0, 0, NULL),
(82, 8, 14, NULL, 60, 'viewing', '2025-08-03 17:46:57', '2025-08-03 17:46:57', 0, 0, NULL),
(83, 8, 14, NULL, 120, 'viewing', '2025-08-03 17:47:57', '2025-08-03 17:47:57', 0, 0, NULL),
(84, 8, 14, 32, 60, 'viewing', '2025-08-05 07:29:25', '2025-08-05 07:29:25', 0, 0, NULL),
(85, 8, 14, NULL, 60, 'viewing', '2025-08-05 07:44:12', '2025-08-05 07:44:12', 0, 0, NULL),
(86, 7, 14, 35, 60, 'viewing', '2025-08-05 07:48:28', '2025-08-05 07:48:28', 0, 0, NULL),
(87, 1, 14, 38, 60, 'viewing', '2025-08-08 04:40:21', '2025-08-08 04:40:21', 0, 0, NULL),
(88, 1, 14, NULL, 60, 'viewing', '2025-08-08 04:53:02', '2025-08-08 04:53:02', 0, 0, NULL),
(89, 12, 17, NULL, 60, 'viewing', '2025-08-29 13:02:00', '2025-08-29 13:02:00', 0, 0, NULL),
(90, 13, 14, NULL, 60, 'viewing', '2025-09-12 12:16:02', '2025-09-12 12:16:02', 0, 0, NULL),
(91, 13, 14, NULL, 120, 'viewing', '2025-09-12 12:17:02', '2025-09-12 12:17:02', 0, 0, NULL),
(92, 14, 14, 32, 60, 'viewing', '2025-09-19 14:42:33', '2025-09-19 14:42:33', 0, 0, NULL),
(93, 14, 14, 35, 60, 'viewing', '2025-09-19 14:44:55', '2025-09-19 14:44:55', 0, 0, NULL),
(94, 14, 14, 35, 60, 'viewing', '2025-09-19 14:47:44', '2025-09-19 14:47:44', 0, 0, NULL),
(95, 14, 14, 38, 60, 'viewing', '2025-09-19 14:51:07', '2025-09-19 14:51:07', 0, 0, NULL),
(96, 14, 14, 38, 113, 'viewing', '2025-09-19 14:52:07', '2025-09-19 14:52:07', 0, 0, NULL),
(97, 14, 14, 38, 60, 'viewing', '2025-09-19 14:53:51', '2025-09-19 14:53:51', 0, 0, NULL),
(98, 14, 14, 38, 91, 'viewing', '2025-09-19 14:54:51', '2025-09-19 14:54:51', 0, 0, NULL),
(99, 14, 14, 38, 151, 'viewing', '2025-09-19 14:55:51', '2025-09-19 14:55:51', 0, 0, NULL),
(100, 14, 14, 38, 211, 'viewing', '2025-09-19 14:56:51', '2025-09-19 14:56:51', 0, 0, NULL),
(101, 14, 14, 38, 271, 'viewing', '2025-09-19 14:57:51', '2025-09-19 14:57:51', 0, 0, NULL),
(102, 14, 14, 38, 331, 'viewing', '2025-09-19 14:58:51', '2025-09-19 14:58:51', 0, 0, NULL),
(103, 14, 14, 38, 60, 'viewing', '2025-09-19 15:03:19', '2025-09-19 15:03:19', 0, 0, NULL),
(104, 14, 14, 38, 60, 'viewing', '2025-09-19 15:05:01', '2025-09-19 15:05:01', 0, 0, NULL),
(105, 14, 14, 38, 120, 'viewing', '2025-09-19 15:06:01', '2025-09-19 15:06:01', 0, 0, NULL),
(106, 14, 14, 38, 60, 'viewing', '2025-09-19 15:07:10', '2025-09-19 15:07:10', 0, 0, NULL),
(107, 14, 14, 38, 120, 'viewing', '2025-09-19 15:08:10', '2025-09-19 15:08:10', 0, 0, NULL),
(108, 14, 14, 38, 180, 'viewing', '2025-09-19 15:09:10', '2025-09-19 15:09:10', 0, 0, NULL),
(109, 14, 14, 38, 240, 'viewing', '2025-09-19 15:10:10', '2025-09-19 15:10:10', 0, 0, NULL),
(110, 14, 14, 38, 300, 'viewing', '2025-09-19 15:11:10', '2025-09-19 15:11:10', 0, 0, NULL),
(111, 14, 14, 38, 360, 'viewing', '2025-09-19 15:12:10', '2025-09-19 15:12:10', 0, 0, NULL),
(112, 14, 14, 38, 420, 'viewing', '2025-09-19 15:13:10', '2025-09-19 15:13:10', 0, 0, NULL),
(113, 14, 14, 38, 61, 'viewing', '2025-09-19 15:15:41', '2025-09-19 15:15:41', 0, 0, NULL),
(114, 14, 14, 38, 102, 'viewing', '2025-09-19 15:16:41', '2025-09-19 15:16:41', 0, 0, NULL),
(115, 14, 14, 38, 162, 'viewing', '2025-09-19 15:17:41', '2025-09-19 15:17:41', 0, 0, NULL),
(116, 15, 14, NULL, 60, 'viewing', '2025-09-19 16:10:39', '2025-09-19 16:10:39', 0, 0, NULL),
(117, 15, 14, NULL, 60, 'viewing', '2025-09-19 16:22:00', '2025-09-19 16:22:00', 0, 0, NULL),
(118, 16, 19, NULL, 60, 'viewing', '2025-10-16 14:18:10', '2025-10-16 14:18:10', 0, 0, NULL),
(119, 16, 19, 48, 60, 'viewing', '2025-10-16 14:19:31', '2025-10-16 14:19:31', 0, 0, NULL),
(192, 19, 19, NULL, 60, 'viewing', '2025-10-28 05:36:38', '2025-10-28 05:36:38', 0, 0, NULL),
(193, 19, 19, NULL, 120, 'viewing', '2025-10-28 05:37:38', '2025-10-28 05:37:38', 0, 0, NULL),
(194, 20, 19, NULL, 60, 'viewing', '2025-10-28 05:54:22', '2025-10-28 05:54:22', 0, 0, NULL),
(195, 20, 19, NULL, 120, 'viewing', '2025-10-28 05:55:22', '2025-10-28 05:55:22', 0, 0, NULL),
(196, 20, 19, NULL, 180, 'viewing', '2025-10-28 05:56:22', '2025-10-28 05:56:22', 0, 0, NULL),
(197, 20, 19, NULL, 220, 'viewing', '2025-10-28 05:57:22', '2025-10-28 05:57:22', 0, 0, NULL),
(198, 20, 19, NULL, 280, 'viewing', '2025-10-28 05:58:22', '2025-10-28 05:58:22', 0, 0, NULL),
(199, 20, 19, NULL, 360, 'viewing', '2025-10-28 05:59:22', '2025-10-28 05:59:22', 0, 0, NULL),
(200, 20, 19, NULL, 60, 'viewing', '2025-10-28 06:06:41', '2025-10-28 06:06:41', 0, 0, NULL),
(201, 20, 19, NULL, 60, 'viewing', '2025-10-28 06:26:04', '2025-10-28 06:26:04', 0, 0, NULL),
(202, 20, 19, NULL, 60, 'viewing', '2025-10-28 06:42:35', '2025-10-28 06:42:35', 0, 0, NULL),
(203, 20, 19, NULL, 120, 'viewing', '2025-10-28 06:43:35', '2025-10-28 06:43:35', 0, 0, NULL),
(204, 20, 19, NULL, 60, 'viewing', '2025-10-28 06:56:26', '2025-10-28 06:56:26', 0, 0, NULL),
(205, 20, 19, NULL, 60, 'viewing', '2025-10-28 15:24:33', '2025-10-28 15:24:33', 0, 0, NULL),
(206, 20, 19, NULL, 120, 'viewing', '2025-10-28 15:25:33', '2025-10-28 15:25:33', 0, 0, NULL),
(207, 20, 19, NULL, 151, 'viewing', '2025-10-28 15:26:33', '2025-10-28 15:26:33', 0, 0, NULL),
(208, 20, 19, NULL, 211, 'viewing', '2025-10-28 15:27:33', '2025-10-28 15:27:33', 0, 0, NULL),
(209, 20, 19, NULL, 271, 'viewing', '2025-10-28 15:28:33', '2025-10-28 15:28:33', 0, 0, NULL),
(210, 20, 19, NULL, 60, 'viewing', '2025-10-28 15:30:37', '2025-10-28 15:30:37', 0, 0, NULL),
(211, 20, 19, NULL, 120, 'viewing', '2025-10-28 15:31:37', '2025-10-28 15:31:37', 0, 0, NULL),
(212, 20, 19, NULL, 180, 'viewing', '2025-10-28 15:32:37', '2025-10-28 15:32:37', 0, 0, NULL),
(213, 20, 19, NULL, 212, 'viewing', '2025-10-28 15:33:37', '2025-10-28 15:33:37', 0, 0, NULL),
(214, 20, 19, NULL, 267, 'viewing', '2025-10-28 15:34:37', '2025-10-28 15:34:37', 0, 0, NULL),
(215, 20, 19, NULL, 60, 'viewing', '2025-10-28 15:36:47', '2025-10-28 15:36:47', 0, 0, NULL),
(216, 20, 19, NULL, 112, 'viewing', '2025-10-28 15:37:46', '2025-10-28 15:37:46', 0, 0, NULL),
(217, 20, 19, NULL, 138, 'viewing', '2025-10-28 15:38:46', '2025-10-28 15:38:46', 0, 0, NULL),
(218, 20, 19, NULL, 198, 'viewing', '2025-10-28 15:39:46', '2025-10-28 15:39:46', 0, 0, NULL),
(219, 20, 19, NULL, 258, 'viewing', '2025-10-28 15:40:46', '2025-10-28 15:40:46', 0, 0, NULL),
(220, 20, 19, NULL, 318, 'viewing', '2025-10-28 15:41:46', '2025-10-28 15:41:46', 0, 0, NULL),
(221, 20, 19, NULL, 438, 'viewing', '2025-10-28 15:43:04', '2025-10-28 15:43:04', 0, 0, NULL),
(222, 21, 19, NULL, 60, 'viewing', '2025-11-11 14:00:24', '2025-11-11 14:00:24', 0, 0, NULL),
(223, 21, 20, NULL, 60, 'viewing', '2025-11-11 14:59:20', '2025-11-11 14:59:20', 0, 0, NULL),
(224, 21, 20, NULL, 60, 'viewing', '2025-11-11 15:06:03', '2025-11-11 15:06:03', 0, 0, NULL),
(225, 22, 19, NULL, 60, 'viewing', '2025-11-11 15:47:10', '2025-11-11 15:47:10', 0, 0, NULL),
(226, 22, 20, NULL, 60, 'viewing', '2025-11-11 15:49:33', '2025-11-11 15:49:33', 0, 0, NULL),
(227, 21, 19, 49, 60, 'viewing', '2025-11-11 16:08:33', '2025-11-11 16:08:33', 0, 0, NULL),
(228, 21, 19, 49, 105, 'viewing', '2025-11-11 16:09:33', '2025-11-11 16:09:33', 0, 0, NULL),
(229, 21, 19, 49, 60, 'viewing', '2025-11-11 16:11:32', '2025-11-11 16:11:32', 0, 0, NULL),
(230, 21, 19, 49, 106, 'viewing', '2025-11-11 16:12:32', '2025-11-11 16:12:32', 0, 0, NULL),
(231, 22, 19, 49, 60, 'viewing', '2025-11-11 16:16:46', '2025-11-11 16:16:46', 0, 0, NULL),
(232, 22, 19, 49, 92, 'viewing', '2025-11-11 16:17:46', '2025-11-11 16:17:46', 0, 0, NULL),
(233, 22, 19, 49, 152, 'viewing', '2025-11-11 16:18:46', '2025-11-11 16:18:46', 0, 0, NULL),
(234, 23, 19, NULL, 60, 'viewing', '2025-11-11 16:21:14', '2025-11-11 16:21:14', 0, 0, NULL),
(235, 23, 19, NULL, 61, 'viewing', '2025-11-11 16:25:06', '2025-11-11 16:25:06', 0, 0, NULL),
(236, 24, 19, NULL, 60, 'viewing', '2025-11-12 14:39:52', '2025-11-12 14:39:52', 0, 0, NULL),
(237, 24, 19, NULL, 60, 'viewing', '2025-11-12 14:44:12', '2025-11-12 14:44:12', 0, 0, NULL),
(238, 24, 19, NULL, 60, 'viewing', '2025-11-12 14:49:00', '2025-11-12 14:49:00', 0, 0, NULL),
(239, 24, 19, NULL, 120, 'viewing', '2025-11-12 14:50:00', '2025-11-12 14:50:00', 0, 0, NULL),
(240, 24, 19, NULL, 180, 'viewing', '2025-11-12 14:51:00', '2025-11-12 14:51:00', 0, 0, NULL),
(241, 24, 19, NULL, 237, 'viewing', '2025-11-12 14:52:00', '2025-11-12 14:52:00', 0, 0, NULL),
(242, 24, 19, NULL, 255, 'viewing', '2025-11-12 14:53:00', '2025-11-12 14:53:00', 0, 0, NULL),
(243, 24, 19, NULL, 60, 'viewing', '2025-11-12 15:01:04', '2025-11-12 15:01:04', 0, 0, NULL),
(244, 24, 19, NULL, 120, 'viewing', '2025-11-12 15:02:04', '2025-11-12 15:02:04', 0, 0, NULL),
(245, 24, 19, 48, 60, 'viewing', '2025-11-12 15:03:10', '2025-11-12 15:03:10', 0, 0, NULL),
(246, 24, 19, 48, 77, 'viewing', '2025-11-12 15:04:10', '2025-11-12 15:04:10', 0, 0, NULL),
(247, 24, 19, 48, 60, 'viewing', '2025-11-12 15:09:08', '2025-11-12 15:09:08', 0, 0, NULL),
(248, 24, 19, 48, 120, 'viewing', '2025-11-12 15:10:08', '2025-11-12 15:10:08', 0, 0, NULL),
(249, 25, 20, 63, 60, 'viewing', '2025-11-12 15:24:16', '2025-11-12 15:24:16', 0, 0, NULL),
(250, 25, 20, 63, 119, 'viewing', '2025-11-12 15:25:16', '2025-11-12 15:25:16', 0, 0, NULL),
(251, 25, 20, NULL, 60, 'viewing', '2025-11-12 15:31:07', '2025-11-12 15:31:07', 0, 0, NULL),
(252, 25, 20, NULL, 60, 'viewing', '2025-11-12 16:32:37', '2025-11-12 16:32:37', 0, 0, NULL),
(253, 25, 20, NULL, 120, 'viewing', '2025-11-12 16:33:37', '2025-11-12 16:33:37', 0, 0, NULL),
(254, 25, 20, NULL, 180, 'viewing', '2025-11-12 16:34:37', '2025-11-12 16:34:37', 0, 0, NULL),
(255, 25, 20, NULL, 240, 'viewing', '2025-11-12 16:35:36', '2025-11-12 16:35:36', 0, 0, NULL),
(256, 25, 20, NULL, 300, 'viewing', '2025-11-12 16:36:36', '2025-11-12 16:36:36', 0, 0, NULL),
(257, 25, 20, NULL, 60, 'viewing', '2025-11-12 16:38:21', '2025-11-12 16:38:21', 0, 0, NULL),
(258, 21, 19, 50, 60, 'viewing', '2025-11-12 16:59:14', '2025-11-12 16:59:14', 0, 0, NULL),
(259, 25, 20, NULL, 60, 'viewing', '2025-11-12 17:09:32', '2025-11-12 17:09:32', 0, 0, NULL),
(260, 25, 20, NULL, 120, 'viewing', '2025-11-12 17:10:32', '2025-11-12 17:10:32', 0, 0, NULL),
(261, 25, 20, NULL, 60, 'viewing', '2025-11-12 17:12:45', '2025-11-12 17:12:45', 0, 0, NULL),
(262, 25, 20, NULL, 120, 'viewing', '2025-11-12 17:13:45', '2025-11-12 17:13:45', 0, 0, NULL),
(263, 25, 20, NULL, 180, 'viewing', '2025-11-12 17:14:45', '2025-11-12 17:14:45', 0, 0, NULL),
(264, 25, 20, NULL, 210, 'viewing', '2025-11-12 17:15:45', '2025-11-12 17:15:45', 0, 0, NULL),
(265, 25, 20, NULL, 60, 'viewing', '2025-11-12 17:18:11', '2025-11-12 17:18:11', 0, 0, NULL),
(266, 25, 20, NULL, 64, 'viewing', '2025-11-12 17:19:11', '2025-11-12 17:19:11', 0, 0, NULL),
(267, 25, 20, NULL, 180, 'viewing', '2025-11-12 17:20:11', '2025-11-12 17:20:11', 0, 0, NULL),
(268, 25, 20, NULL, 240, 'viewing', '2025-11-12 17:21:11', '2025-11-12 17:21:11', 0, 0, NULL),
(269, 25, 20, NULL, 300, 'viewing', '2025-11-12 17:22:11', '2025-11-12 17:22:11', 0, 0, NULL),
(270, 25, 20, NULL, 304, 'viewing', '2025-11-12 17:23:11', '2025-11-12 17:23:11', 0, 0, NULL),
(271, 26, 20, NULL, 60, 'viewing', '2025-11-12 17:28:34', '2025-11-12 17:28:34', 0, 0, NULL),
(272, 25, 20, NULL, 60, 'viewing', '2025-11-12 17:32:38', '2025-11-12 17:32:38', 0, 0, NULL),
(273, 25, 20, NULL, 120, 'viewing', '2025-11-12 17:33:38', '2025-11-12 17:33:38', 0, 0, NULL),
(274, 26, 20, NULL, 60, 'viewing', '2025-11-12 17:48:28', '2025-11-12 17:48:28', 0, 0, NULL),
(275, 27, 20, NULL, 60, 'viewing', '2025-11-12 17:54:57', '2025-11-12 17:54:57', 0, 0, NULL),
(276, 24, 19, NULL, 60, 'viewing', '2025-11-12 18:00:06', '2025-11-12 18:00:06', 0, 0, NULL),
(277, 21, 20, NULL, 60, 'viewing', '2025-11-14 15:01:23', '2025-11-14 15:01:23', 0, 0, NULL),
(278, 21, 20, NULL, 60, 'viewing', '2025-11-15 15:56:42', '2025-11-15 15:56:42', 0, 0, NULL),
(279, 27, 20, NULL, 60, 'viewing', '2025-11-15 16:36:06', '2025-11-15 16:36:06', 0, 0, NULL),
(280, 27, 20, NULL, 120, 'viewing', '2025-11-15 16:37:06', '2025-11-15 16:37:06', 0, 0, NULL),
(281, 27, 20, NULL, 60, 'viewing', '2025-11-15 16:42:21', '2025-11-15 16:42:21', 0, 0, NULL),
(282, 27, 20, NULL, 120, 'viewing', '2025-11-15 16:43:21', '2025-11-15 16:43:21', 0, 0, NULL),
(283, 26, 20, NULL, 60, 'viewing', '2025-11-15 16:45:47', '2025-11-15 16:45:47', 0, 0, NULL),
(284, 25, 20, NULL, 60, 'viewing', '2025-11-17 13:07:37', '2025-11-17 13:07:37', 0, 0, NULL),
(285, 27, 21, NULL, 60, 'viewing', '2025-11-17 14:30:54', '2025-11-17 14:30:54', 0, 0, NULL),
(286, 27, 21, NULL, 120, 'viewing', '2025-11-17 14:31:53', '2025-11-17 14:31:53', 0, 0, NULL),
(287, 27, 21, NULL, 180, 'viewing', '2025-11-17 14:32:53', '2025-11-17 14:32:53', 0, 0, NULL),
(288, 27, 21, NULL, 240, 'viewing', '2025-11-17 14:33:54', '2025-11-17 14:33:54', 0, 0, NULL),
(289, 27, 21, NULL, 300, 'viewing', '2025-11-17 14:34:53', '2025-11-17 14:34:53', 0, 0, NULL),
(290, 27, 21, NULL, 360, 'viewing', '2025-11-17 14:35:53', '2025-11-17 14:35:53', 0, 0, NULL),
(291, 27, 21, NULL, 420, 'viewing', '2025-11-17 14:36:54', '2025-11-17 14:36:54', 0, 0, NULL),
(292, 27, 21, NULL, 480, 'viewing', '2025-11-17 14:37:54', '2025-11-17 14:37:54', 0, 0, NULL),
(293, 27, 21, NULL, 562, 'viewing', '2025-11-17 14:39:15', '2025-11-17 14:39:15', 0, 0, NULL),
(294, 27, 21, NULL, 600, 'viewing', '2025-11-17 14:39:53', '2025-11-17 14:39:53', 0, 0, NULL),
(295, 28, 20, NULL, 60, 'viewing', '2025-11-17 14:58:43', '2025-11-17 14:58:43', 0, 0, NULL),
(296, 28, 20, NULL, 92, 'viewing', '2025-11-17 14:59:43', '2025-11-17 14:59:43', 0, 0, NULL),
(297, 28, 20, NULL, 152, 'viewing', '2025-11-17 15:00:43', '2025-11-17 15:00:43', 0, 0, NULL),
(298, 28, 20, NULL, 212, 'viewing', '2025-11-17 15:01:43', '2025-11-17 15:01:43', 0, 0, NULL),
(299, 26, 19, NULL, 60, 'viewing', '2025-11-18 05:09:03', '2025-11-18 05:09:03', 0, 0, NULL),
(300, 26, 19, NULL, 89, 'viewing', '2025-11-18 05:10:03', '2025-11-18 05:10:03', 0, 0, NULL),
(301, 21, 19, 50, 60, 'viewing', '2025-11-18 05:13:18', '2025-11-18 05:13:18', 0, 0, NULL),
(302, 29, 20, NULL, 60, 'viewing', '2025-11-19 15:01:16', '2025-11-19 15:01:16', 0, 0, NULL),
(303, 21, 19, 50, 60, 'viewing', '2025-11-20 05:47:04', '2025-11-20 05:47:04', 0, 0, NULL),
(304, 31, 22, NULL, 60, 'viewing', '2025-11-23 14:32:34', '2025-11-23 14:32:34', 0, 0, NULL),
(305, 31, 22, NULL, 120, 'viewing', '2025-11-23 14:33:34', '2025-11-23 14:33:34', 0, 0, NULL),
(306, 31, 22, NULL, 180, 'viewing', '2025-11-23 14:34:34', '2025-11-23 14:34:34', 0, 0, NULL),
(307, 31, 22, NULL, 60, 'viewing', '2025-11-23 14:37:19', '2025-11-23 14:37:19', 0, 0, NULL),
(308, 31, 22, NULL, 60, 'viewing', '2025-11-23 14:43:35', '2025-11-23 14:43:35', 0, 0, NULL),
(309, 31, 22, NULL, 91, 'viewing', '2025-11-23 14:44:34', '2025-11-23 14:44:34', 0, 0, NULL),
(310, 32, 22, NULL, 60, 'viewing', '2025-11-24 06:24:37', '2025-11-24 06:24:37', 0, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `final_quizzes`
--

CREATE TABLE `final_quizzes` (
  `id` int(11) NOT NULL,
  `module_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `allow_retake` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `final_quizzes`
--

INSERT INTO `final_quizzes` (`id`, `module_id`, `title`, `created_at`, `allow_retake`) VALUES
(13, 22, 'Introduction to IT Computing', '2025-11-23 05:21:55', 0);

-- --------------------------------------------------------

--
-- Table structure for table `final_quiz_questions`
--

CREATE TABLE `final_quiz_questions` (
  `id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `option1` varchar(255) NOT NULL,
  `option2` varchar(255) NOT NULL,
  `option3` varchar(255) NOT NULL,
  `option4` varchar(255) NOT NULL,
  `correct_answer` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `final_quiz_questions`
--

INSERT INTO `final_quiz_questions` (`id`, `quiz_id`, `question_text`, `option1`, `option2`, `option3`, `option4`, `correct_answer`) VALUES
(18, 13, 'What is the main purpose of Internet Security?', 'To protect data during online transactions', 'To speed up the internet connection', 'To increase computer storage', 'To install software updates', 1),
(19, 13, 'Internet security relies heavily on which technology for protecting data?', 'Encryption', 'Game engines', 'Photo editing software', 'Graphic rendering', 1),
(20, 13, 'Cybersecurity refers to the protection of which of the following?', 'Game consoles', 'Internet-connected systems', 'Musical instruments', 'Television signals', 2),
(21, 13, 'Which is a major challenge in cybersecurity?', 'Ever-evolving security risks', 'Slow computer hardware', 'Poor-quality monitors', 'Weak speakers', 1),
(22, 13, 'Which cybersecurity area focuses on protecting apps through updates and testing?', 'Network security', 'Application security', 'Mobile security', 'Server hosting', 2),
(23, 13, 'Identity management is concerned with what?', 'Users\\\' access levels', 'Screen brightness', 'Keyboard size', 'Sound quality', 1),
(24, 13, 'What is cybercrime?', 'Crime involving food contamination', 'Crime where a computer is the tool or target', 'Theft inside supermarkets', 'Misuse of public transportation', 2),
(25, 13, 'Cyberbullying occurs between which group?', 'Two minors', 'Two adults', 'Parents and teachers', 'Customers and sellers', 1),
(26, 13, 'Phishing messages typically attempt to:', 'Trick users into giving usernames and passwords', 'Speed up downloads', 'Install games', 'Test computer performance', 1),
(27, 13, 'Pharming redirects users to:', 'Weather reports', 'A fake website', 'App stores', 'Email inbox', 2),
(28, 13, 'Which malware displays unwanted advertisements?', 'Spyware', 'Adware', 'Worm', 'Botnet', 2),
(29, 13, 'Spyware is designed to:', 'Entertain the user', 'Monitor a user secretly', 'Improve internet speed', 'Install drivers', 2),
(30, 13, 'A computer virus requires what to spread?', 'A host file', 'Solar power', 'A barcode', 'A printer', 1),
(31, 13, 'The “I Love You” virus was primarily spread through:', 'Weather apps', 'Email attachments', 'Gaming consoles', 'USB keyboards', 2),
(32, 13, 'What triggers a logic bomb?', 'A specific condition', 'Screen brightness change', 'Temperature drop', 'Battery charging', 1),
(33, 13, 'Worms differ from viruses because worms:', 'Require a host', 'Self-replicate without a host', 'Cannot spread', 'Only work on mobile', 2),
(34, 13, 'Which hacking type is legal and used for security testing?', 'Black-hat', 'Gray-hat', 'White-hat', 'Random hacking', 3),
(35, 13, 'Keyloggers record:', 'Screen brightness', 'Every keystroke typed', 'Battery usage', 'Computer temperature', 2),
(36, 13, 'A botnet is a network of:', 'Printers', 'Hacked computers controlled remotely', 'Speakers', 'Wi-Fi routers', 2),
(37, 13, 'Ransomware prevents you from using your computer unless you:', 'Restart it', 'Replace the battery', 'Pay a fee', 'Update your wallpaper', 3),
(38, 13, 'A firewall is designed to:', 'Improve graphics', 'Block unauthorized access', 'Increase volume', 'Boost internet ads', 2),
(39, 13, 'Antivirus software protects against:', 'Clothing defects', 'Viruses, Trojans, worms', 'Slow internet', 'Dust on the keyboard', 2),
(40, 13, 'What does NAT (Network Address Translation) do?', 'Cools the computer', 'Shields private devices from the internet', 'Controls sound output', 'Changes screen size', 2),
(41, 13, 'Why should operating systems be regularly updated?', 'To change wallpapers', 'To fix vulnerabilities', 'To slow the system', 'To remove icons', 2),
(42, 13, 'What is a drive-by download?', 'Installing software while driving', 'Automatic installation of malware when visiting a website', 'Downloading apps from stores', 'Saving files to a USB', 2);

-- --------------------------------------------------------

--
-- Table structure for table `final_quiz_retakes`
--

CREATE TABLE `final_quiz_retakes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `used` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `focus_events`
--

CREATE TABLE `focus_events` (
  `id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `event_type` enum('focus_start','focus_end','unfocus_start','unfocus_end') NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `duration_seconds` int(11) DEFAULT NULL,
  `confidence_score` decimal(3,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `modules`
--

CREATE TABLE `modules` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('draft','published') DEFAULT 'draft'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `modules`
--

INSERT INTO `modules` (`id`, `title`, `description`, `image_path`, `created_at`, `updated_at`, `status`) VALUES
(22, 'MODULE 4: Introduction to IT Computing', 'This lesson will discuss the basic concepts and principles of internet security as well as different internet threats.  This lesson will also provide activities and exercises that will practice the students’ competence in identifying internet threats to avoid being victims of cybercrimes.', '/capstone/modulephotoshow/module_1763906870_e9c1a0f7f12693f7.jpg', '2025-11-23 01:47:24', '2025-11-23 14:12:54', 'published');

-- --------------------------------------------------------

--
-- Table structure for table `module_completions`
--

CREATE TABLE `module_completions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `completion_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `final_quiz_score` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `module_completions`
--

INSERT INTO `module_completions` (`id`, `user_id`, `module_id`, `completion_date`, `final_quiz_score`) VALUES
(1, 18, 19, '2025-10-27 17:23:32', 100),
(4, 19, 19, '2025-10-27 20:41:50', 8),
(5, 20, 19, '2025-10-31 19:38:44', 0),
(13, 21, 20, '2025-11-11 15:06:23', 0),
(15, 23, 20, '2025-11-11 16:22:15', 11),
(16, 23, 19, '2025-11-11 16:26:02', 8),
(22, 27, 20, '2025-11-17 18:23:47', 0),
(24, 26, 20, '2025-11-15 16:47:00', 1),
(25, 25, 20, '2025-11-17 13:20:34', 0),
(27, 25, 21, '2025-11-17 13:13:24', 1),
(31, 26, 21, '2025-11-17 13:56:49', 1),
(32, 27, 21, '2025-11-17 14:29:59', 1),
(33, 28, 20, '2025-11-22 14:08:22', 1),
(70, 28, 21, '2025-11-22 14:08:09', 1),
(86, 29, 20, '2025-11-20 02:14:01', 0),
(93, 29, 21, '2025-11-19 16:54:26', 1),
(102, 30, 20, '2025-11-19 18:07:39', 0),
(130, 31, 22, '2025-11-24 05:30:52', 7),
(132, 32, 22, '2025-11-24 06:25:52', 7);

-- --------------------------------------------------------

--
-- Table structure for table `module_parts`
--

CREATE TABLE `module_parts` (
  `id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `subtitle` varchar(255) DEFAULT NULL,
  `content` text NOT NULL,
  `has_subquiz` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `order_index` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `module_parts`
--

INSERT INTO `module_parts` (`id`, `module_id`, `title`, `subtitle`, `content`, `has_subquiz`, `created_at`, `order_index`) VALUES
(56, 22, 'Introduction to Internet Security', NULL, '', 0, '2025-11-23 04:13:21', 0),
(57, 22, 'Cybersecurity Challenges', NULL, '', 0, '2025-11-23 04:42:35', 0);

-- --------------------------------------------------------

--
-- Table structure for table `module_sections`
--

CREATE TABLE `module_sections` (
  `id` int(11) NOT NULL,
  `module_part_id` int(11) NOT NULL,
  `subtitle` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `section_order` int(11) NOT NULL,
  `has_quiz` tinyint(1) DEFAULT 0,
  `order_index` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `module_sections`
--

INSERT INTO `module_sections` (`id`, `module_part_id`, `subtitle`, `content`, `section_order`, `has_quiz`, `order_index`) VALUES
(77, 56, 'Understanding Internet and Cybersecurity', '<p class=\"MsoNormal\"><strong>Contents:</strong></p>\r\n<ul style=\"margin-top: 0cm;\" type=\"disc\">\r\n<li class=\"MsoNormal\" style=\"mso-list: l0 level1 lfo1; tab-stops: list 36.0pt;\"><strong>Definition of Internet Security:</strong><br>Protection for online transactions, browser activities, and data exchange using encryption and authentication standards.</li>\r\n<li class=\"MsoNormal\" style=\"mso-list: l0 level1 lfo1; tab-stops: list 36.0pt;\"><strong>Definition of Cybersecurity:</strong><br>Safeguarding internet-connected systems&mdash;hardware, software, and data&mdash;from unauthorized access and attacks.</li>\r\n<li class=\"MsoNormal\" style=\"mso-list: l0 level1 lfo1; tab-stops: list 36.0pt;\"><strong>Importance:</strong><br>Governments, companies, and individuals rely on cybersecurity to protect financial data, personal information, and sensitive records.</li>\r\n<li class=\"MsoNormal\" style=\"mso-list: l0 level1 lfo1; tab-stops: list 36.0pt;\"><strong>Key Areas of Cybersecurity:</strong><br>Network, Application, Endpoint, Data, Cloud, Identity, and Mobile Security.</li>\r\n</ul>', 1, 0, 0),
(78, 56, 'What is Internet Security?', '<p class=\"MsoNormal\" style=\"margin-left: 92.7pt; text-indent: -18.0pt; mso-list: l0 level1 lfo1;\"><!-- [if !supportLists]--><span style=\"font-family: \'Arial\',sans-serif; mso-fareast-font-family: Arial;\"><span style=\"mso-list: Ignore;\">-<span style=\"font: 7.0pt \'Times New Roman\';\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span><!--[endif]--><em style=\"mso-bidi-font-style: normal;\">is a catch-all term for a very broad issue covering security for transactions made over the Internet. Generally, Internet security encompasses browser security, the security of data entered through a Web form, and overall authentication and protection of data sent via Internet Protocol. </em></p>\r\n<p class=\"MsoNormal\" style=\"margin-left: 92.7pt; text-indent: -18.0pt; mso-list: l0 level1 lfo1;\"><!-- [if !supportLists]--><span style=\"font-family: \'Arial\',sans-serif; mso-fareast-font-family: Arial;\"><span style=\"mso-list: Ignore;\">-<span style=\"font: 7.0pt \'Times New Roman\';\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span><!--[endif]--><em style=\"mso-bidi-font-style: normal;\">Internet security is a branch of computer security which comprises various security measures exercised for ensuring the security of transactions done online. In the process, the internet security prevents attacks targeted at browsers, network, operating systems, and other applications. Today, businesses and governments are more concerned about safeguarding from Cyber attacks and malware programs that originate from the internet. The main aim of Internet security is to set up precise rules and regulations that can deflect attacks that arise from the Internet.</em></p>\r\n<p class=\"MsoNormal\" style=\"margin-left: 92.7pt; text-indent: -18.0pt; mso-list: l0 level1 lfo1;\"><!-- [if !supportLists]--><span style=\"font-family: \'Arial\',sans-serif; mso-fareast-font-family: Arial;\"><span style=\"mso-list: Ignore;\">-<span style=\"font: 7.0pt \'Times New Roman\';\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span><!--[endif]--><em style=\"mso-bidi-font-style: normal;\">Internet security relies on specific resources and standards for protecting data that gets sent through the Internet. This includes various kinds of encryption such as Pretty Good Privacy (PGP)</em></p>\r\n<p class=\"MsoNormal\" style=\"margin-left: 92.7pt; text-indent: -18.0pt; mso-list: l0 level1 lfo1;\"><!-- [if !supportLists]--><span style=\"font-family: \'Arial\',sans-serif; mso-fareast-font-family: Arial;\"><span style=\"mso-list: Ignore;\">-<span style=\"font: 7.0pt \'Times New Roman\';\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span><!--[endif]--><em style=\"mso-bidi-font-style: normal;\">Internet security is generally becoming a top priority for both businesses and governments. </em></p>', 2, 0, 0),
(79, 57, 'Evolving Risks and System Vulnerabilities', '<ul style=\"margin-top: 0cm;\" type=\"disc\">\r\n<li class=\"MsoNormal\" style=\"mso-list: l0 level1 lfo1; tab-stops: list 36.0pt;\">Cybersecurity must coordinate across all areas of an organization.</li>\r\n<li class=\"MsoNormal\" style=\"mso-list: l0 level1 lfo1; tab-stops: list 36.0pt;\">Common challenges include:</li>\r\n<ul style=\"margin-top: 0cm;\" type=\"circle\">\r\n<li class=\"MsoNormal\" style=\"mso-list: l0 level2 lfo1; tab-stops: list 72.0pt;\">Network attacks and intrusions</li>\r\n<li class=\"MsoNormal\" style=\"mso-list: l0 level2 lfo1; tab-stops: list 72.0pt;\">Data breaches and unauthorized access</li>\r\n<li class=\"MsoNormal\" style=\"mso-list: l0 level2 lfo1; tab-stops: list 72.0pt;\">Weak endpoints and remote vulnerabilities</li>\r\n<li class=\"MsoNormal\" style=\"mso-list: l0 level2 lfo1; tab-stops: list 72.0pt;\">Cloud and mobile security issues</li>\r\n</ul>\r\n<li class=\"MsoNormal\" style=\"mso-list: l0 level1 lfo1; tab-stops: list 36.0pt;\"><strong>Modern Approach:</strong><br>Traditional &ldquo;perimeter-based&rdquo; defenses are not enough. Organizations must use <strong>continuous monitoring</strong> and <strong>real-time assessments</strong> (NIST guidelines).</li>\r\n</ul>', 1, 0, 0),
(80, 57, 'What is Cybersecurity?', '<p class=\"MsoNormal\" style=\"margin-left: 92.7pt; text-indent: -18.0pt; mso-list: l0 level1 lfo1;\"><!-- [if !supportLists]--><span style=\"font-family: \'Arial\',sans-serif; mso-fareast-font-family: Arial;\"><span style=\"mso-list: Ignore;\">-<span style=\"font: 7.0pt \'Times New Roman\';\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span><!--[endif]--><em style=\"mso-bidi-font-style: normal;\">is the protection of internet-connected systems, including hardware, software and data, from cyber-attacks. In a computing context, security comprises cybersecurity and physical security -- both are used by enterprises to protect against unauthorized access to data centers and other computerized systems</em></p>\r\n<p class=\"MsoNormal\" style=\"margin-left: 92.7pt; text-indent: -18.0pt; mso-list: l0 level1 lfo1;\"><!-- [if !supportLists]--><span style=\"font-family: \'Arial\',sans-serif; mso-fareast-font-family: Arial;\"><span style=\"mso-list: Ignore;\">-<span style=\"font: 7.0pt \'Times New Roman\';\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span><!--[endif]--><em style=\"mso-bidi-font-style: normal;\">Cyber security is important because government, military, corporate, financial, and medical organizations collect, process, and store unprecedented amounts of data on computers and other devices. A significant portion of that data can be sensitive information, whether that be intellectual property, financial data, personal information, or other types of data for which unauthorized access or exposure could have negative consequences. Organizations transmit sensitive data across networks and to other devices in the course of doing businesses, and cyber security describes the discipline dedicated to protecting that information and the systems used to process or store it.</em></p>', 2, 0, 0),
(81, 57, 'Evolving Risks and System Vulnerabilities', '<ul style=\"margin-top: 0cm;\" type=\"disc\">\r\n<li class=\"MsoNormal\" style=\"text-align: justify; mso-list: l0 level1 lfo1; tab-stops: list 36.0pt;\"><span style=\"font-family: \'Cambria\',serif;\">Cybersecurity must coordinate across all areas of an organization.</span></li>\r\n<li class=\"MsoNormal\" style=\"text-align: justify; mso-list: l0 level1 lfo1; tab-stops: list 36.0pt;\"><span style=\"font-family: \'Cambria\',serif;\">Common challenges include:</span>\r\n<ul style=\"margin-top: 0cm;\" type=\"circle\">\r\n<li class=\"MsoNormal\" style=\"text-align: justify; mso-list: l0 level2 lfo1; tab-stops: list 72.0pt;\"><span style=\"font-family: \'Cambria\',serif;\">Network attacks and intrusions</span></li>\r\n<li class=\"MsoNormal\" style=\"text-align: justify; mso-list: l0 level2 lfo1; tab-stops: list 72.0pt;\"><span style=\"font-family: \'Cambria\',serif;\">Data breaches and unauthorized access</span></li>\r\n<li class=\"MsoNormal\" style=\"text-align: justify; mso-list: l0 level2 lfo1; tab-stops: list 72.0pt;\"><span style=\"font-family: \'Cambria\',serif;\">Weak endpoints and remote vulnerabilities</span></li>\r\n<li class=\"MsoNormal\" style=\"text-align: justify; mso-list: l0 level2 lfo1; tab-stops: list 72.0pt;\"><span style=\"font-family: \'Cambria\',serif;\">Cloud and mobile security issues</span></li>\r\n</ul>\r\n</li>\r\n</ul>\r\n<p><strong><span style=\"font-family: \'Cambria\',serif;\">Modern Approach:</span></strong><span style=\"font-family: \'Cambria\',serif;\"><br>Traditional &ldquo;perimeter-based&rdquo; defenses are not enough. Organizations must use <strong>continuous monitoring</strong> and <strong>real-time assessments</strong> (NIST guidelines). </span></p>\r\n<ul style=\"list-style-type: square;\">\r\n<li><strong style=\"mso-bidi-font-weight: normal;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"font-family: \'Cambria\',serif;\">Network security:&nbsp;</span></em></strong><em style=\"mso-bidi-font-style: normal;\"><span style=\"font-family: \'Cambria\',serif;\">The process of protecting the network from unwanted users, attacks and intrusions.</span></em></li>\r\n<li><strong style=\"mso-bidi-font-weight: normal;\"><em><span style=\"font-family: \'Cambria\',serif;\">Application security:</span></em></strong><em style=\"mso-bidi-font-style: normal;\"><span style=\"font-family: \'Cambria\',serif;\"> Apps require constant updates and testing to ensure these programs are secure from attacks.</span></em></li>\r\n<li><strong style=\"mso-bidi-font-weight: normal;\"><em><span style=\"font-family: \'Cambria\',serif;\">Endpoint security:</span></em></strong><em style=\"mso-bidi-font-style: normal;\"><span style=\"font-family: \'Cambria\',serif;\"> Remote access is a necessary part of business, but can also be a weak point for data. Endpoint security is the process of protecting remote access to a company&rsquo;s network.</span></em></li>\r\n<li><strong style=\"mso-bidi-font-weight: normal;\"><em><span style=\"font-family: \'Cambria\',serif;\">Data security:&nbsp;</span></em></strong><em style=\"mso-bidi-font-style: normal;\"><span style=\"font-family: \'Cambria\',serif;\">Inside of networks and applications is data. Protecting company and customer information is a separate layer of security.</span></em></li>\r\n<li><strong style=\"mso-bidi-font-weight: normal;\"><em><span style=\"font-family: \'Cambria\',serif;\">Identity management:</span></em></strong><em style=\"mso-bidi-font-style: normal;\"><span style=\"font-family: \'Cambria\',serif;\"> Essentially, this is a process of understanding the access every individual has in an organization.</span></em></li>\r\n<li><strong style=\"mso-bidi-font-weight: normal;\"><em><span style=\"font-family: \'Cambria\',serif;\">Database and infrastructure security:</span></em></strong><em style=\"mso-bidi-font-style: normal;\"><span style=\"font-family: \'Cambria\',serif;\"> Everything in a network involves databases and physical equipment. Protecting these devices is equally important.</span></em></li>\r\n<li><strong style=\"mso-bidi-font-weight: normal;\"><em><span style=\"font-family: \'Cambria\',serif;\">Cloud security:</span></em></strong><em style=\"mso-bidi-font-style: normal;\"><span style=\"font-family: \'Cambria\',serif;\"> Many files are in digital environments or &ldquo;the cloud&rdquo;. Protecting data in a 100% online environment presents a large amount of challenges.</span></em></li>\r\n<li><strong style=\"mso-bidi-font-weight: normal;\"><em><span style=\"font-family: \'Cambria\',serif;\">Mobile security:&nbsp;</span></em></strong><em style=\"mso-bidi-font-style: normal;\"><span style=\"font-family: \'Cambria\',serif;\">Cell phones and tablets involve virtually every type of security challenge in and of themselves.</span></em></li>\r\n<li><strong style=\"mso-bidi-font-weight: normal;\"><em><span style=\"font-family: \'Cambria\',serif;\">Disaster recovery/business continuity planning:</span></em></strong><em style=\"mso-bidi-font-style: normal;\"><span style=\"font-family: \'Cambria\',serif;\"> In the event of a breach, natural disaster or other event data must be protected and business must go on. For this, you&rsquo;ll need a plan. End-user education: Users may be employees accessing the network or customers logging on to a company app. Educating good habits (password changes, 2-factor authentication, etc.) is an important part of cybersecurity.</span></em></li>\r\n</ul>\r\n<p class=\"MsoNormal\" style=\"text-align: justify;\"><span style=\"font-family: \'Cambria\',serif;\">The most difficult challenge in cyber security is the ever-evolving nature of security risks themselves. Traditionally, organizations and the government have focused most of their cyber security resources on perimeter security to protect only their most crucial system components and defend against known treats. Today, this approach is insufficient, as the threats advance and change more quickly than organizations can keep up with. As a result, advisory organizations promote more proactive and adaptive approaches to cyber security. Similarly, the National Institute of Standards and Technology (</span><a href=\"http://www.nist.gov/\"><span style=\"font-family: \'Cambria\',serif;\">NIST</span></a><span style=\"font-family: \'Cambria\',serif;\">) issued guidelines in its risk assessment </span><a href=\"https://www.nist.gov/cyberframework\"><span style=\"font-family: \'Cambria\',serif;\">framework</span></a><span style=\"font-family: \'Cambria\',serif;\"> that recommend a shift toward </span><a href=\"https://digitalguardian.com/blog/what-continuous-security-monitoring\"><span style=\"font-family: \'Cambria\',serif;\">continuous monitoring</span></a><span style=\"font-family: \'Cambria\',serif;\"> and real-time assessments, a data-focused approach to security as opposed to the traditional perimeter-based model.</span></p>\r\n<p class=\"MsoNormal\" style=\"margin-left: 72.0pt; text-align: justify; text-indent: -18.0pt; mso-list: l0 level1 lfo1;\"><!-- [if !supportLists]--><span style=\"font-family: \'Noto Sans Symbols\'; mso-fareast-font-family: \'Noto Sans Symbols\'; mso-bidi-font-family: \'Noto Sans Symbols\';\"><span style=\"mso-list: Ignore;\">●<span style=\"font: 7.0pt \'Times New Roman\';\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span><!--[endif]--><strong style=\"mso-bidi-font-weight: normal;\"><span style=\"font-family: \'Cambria\',serif;\">Israel </span></strong><span style=\"font-family: \'Cambria\',serif;\">is one of the leading countries specializes in cybersecurity.</span></p>\r\n<p class=\"MsoNormal\" style=\"margin-left: 72.0pt; text-align: justify; text-indent: -18.0pt; mso-list: l0 level1 lfo1;\"><!-- [if !supportLists]--><span style=\"font-family: \'Noto Sans Symbols\'; mso-fareast-font-family: \'Noto Sans Symbols\'; mso-bidi-font-family: \'Noto Sans Symbols\';\"><span style=\"mso-list: Ignore;\">●<span style=\"font: 7.0pt \'Times New Roman\';\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span><!--[endif]--><span style=\"font-family: \'Cambria\',serif;\"><span style=\"mso-spacerun: yes;\">&nbsp;</span><strong style=\"mso-bidi-font-weight: normal;\">Cybersecurity Threats</strong></span></p>', 3, 0, 0);
INSERT INTO `module_sections` (`id`, `module_part_id`, `subtitle`, `content`, `section_order`, `has_quiz`, `order_index`) VALUES
(82, 57, 'What is Cybercrime?', '<p class=\"MsoNormal\" style=\"margin-bottom: 0cm; text-align: justify; line-height: 106%;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"font-family: \'Cambria\',serif; color: black;\">-Cybercrime is defined as a crime where a computer is the object of the crime or is used as a tool to commit an offense. A cybercriminal may use a device to access a user&rsquo;s personal information, confidential business information, government information, or disable a device.<br><br></span></em></p>\r\n<p class=\"MsoNormal\" style=\"text-align: justify; text-indent: -18.0pt; line-height: 106%; mso-list: l3 level1 lfo1; margin: 0cm 0cm 0cm 36.0pt;\"><!-- [if !supportLists]--><span style=\"font-family: \'Noto Sans Symbols\'; mso-fareast-font-family: \'Noto Sans Symbols\'; mso-bidi-font-family: \'Noto Sans Symbols\'; color: black;\"><span style=\"mso-list: Ignore;\">❖<span style=\"font: 7.0pt \'Times New Roman\';\">&nbsp;&nbsp;&nbsp; </span></span></span><!--[endif]--><strong style=\"mso-bidi-font-weight: normal;\"><span style=\"color: black;\">TYPES OF CYBERCRIME</span></strong></p>\r\n<p class=\"MsoNormal\" style=\"text-align: justify; line-height: 106%; margin: 0cm 0cm 0cm 72.0pt;\"><span style=\"color: black;\"><span style=\"mso-spacerun: yes;\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span></span></p>\r\n<p class=\"MsoNormal\" style=\"text-align: justify; text-indent: -18.0pt; line-height: 106%; mso-list: l2 level1 lfo2; margin: 0cm 0cm 0cm 73.85pt;\"><!-- [if !supportLists]--><strong style=\"mso-bidi-font-weight: normal;\"><span style=\"font-family: \'Times New Roman\',serif; mso-fareast-font-family: \'Times New Roman\'; color: black;\"><span style=\"mso-list: Ignore;\">1.<span style=\"font: 7.0pt \'Times New Roman\';\">&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span></strong><!--[endif]--><strong style=\"mso-bidi-font-weight: normal;\"><span style=\"color: black;\">Personal Cybercrime</span></strong></p>\r\n<p class=\"MsoNormal\" style=\"text-align: justify; text-indent: -18.0pt; line-height: 106%; mso-list: l0 level1 lfo3; margin: 0cm 0cm 0cm 73.85pt;\"><!-- [if !supportLists]--><span style=\"font-family: \'Noto Sans Symbols\'; mso-fareast-font-family: \'Noto Sans Symbols\'; mso-bidi-font-family: \'Noto Sans Symbols\'; color: black;\"><span style=\"mso-list: Ignore;\">▪<span style=\"font: 7.0pt \'Times New Roman\';\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span><!--[endif]--><strong style=\"mso-bidi-font-weight: normal;\"><span style=\"color: black;\">Harassment</span></strong></p>\r\n<p class=\"MsoNormal\" style=\"text-align: justify; text-indent: -18.0pt; line-height: 106%; mso-list: l4 level1 lfo4; margin: 0cm 0cm 0cm 73.85pt;\"><!-- [if !supportLists]--><span style=\"font-family: \'Times New Roman\',serif; mso-fareast-font-family: \'Times New Roman\'; color: black;\"><span style=\"mso-list: Ignore;\">1.<span style=\"font: 7.0pt \'Times New Roman\';\">&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span><!--[endif]--><span style=\"color: black;\">Cyberbullying: between two minors</span></p>\r\n<p class=\"MsoNormal\" style=\"text-align: justify; text-indent: -18.0pt; line-height: 106%; mso-list: l4 level1 lfo4; margin: 0cm 0cm 0cm 73.85pt;\"><!-- [if !supportLists]--><span style=\"font-family: \'Times New Roman\',serif; mso-fareast-font-family: \'Times New Roman\'; color: black;\"><span style=\"mso-list: Ignore;\">2.<span style=\"font: 7.0pt \'Times New Roman\';\">&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span><!--[endif]--><span style=\"color: black;\">Cyber-harassment: between adults</span></p>\r\n<p class=\"MsoNormal\" style=\"text-align: justify; text-indent: -18.0pt; line-height: 106%; mso-list: l4 level1 lfo4; margin: 0cm 0cm 0cm 73.85pt;\"><!-- [if !supportLists]--><span style=\"font-family: \'Times New Roman\',serif; mso-fareast-font-family: \'Times New Roman\'; color: black;\"><span style=\"mso-list: Ignore;\">3.<span style=\"font: 7.0pt \'Times New Roman\';\">&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span><!--[endif]--><span style=\"color: black;\">Cyber-stalking:</span></p>\r\n<p class=\"MsoNormal\" style=\"text-align: justify; text-indent: -18.0pt; line-height: 106%; mso-list: l5 level1 lfo5; margin: 0cm 0cm 0cm 73.85pt;\"><!-- [if !supportLists]--><span style=\"font-family: \'Arial\',sans-serif; mso-fareast-font-family: Arial; color: black;\"><span style=\"mso-list: Ignore;\">&bull;<span style=\"font: 7.0pt \'Times New Roman\';\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span><!--[endif]--><span style=\"color: black;\">More serious in nature</span></p>\r\n<p class=\"MsoNormal\" style=\"text-align: justify; text-indent: -18.0pt; line-height: 106%; mso-list: l5 level1 lfo5; margin: 0cm 0cm 0cm 73.85pt;\"><!-- [if !supportLists]--><span style=\"font-family: \'Arial\',sans-serif; mso-fareast-font-family: Arial; color: black;\"><span style=\"mso-list: Ignore;\">&bull;<span style=\"font: 7.0pt \'Times New Roman\';\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span><!--[endif]--><span style=\"color: black;\">Stalker demonstrates a pattern of harassment</span></p>\r\n<p class=\"MsoNormal\" style=\"text-align: justify; text-indent: -18.0pt; line-height: 106%; mso-list: l5 level1 lfo5; margin: 0cm 0cm 0cm 73.85pt;\"><!-- [if !supportLists]--><span style=\"font-family: \'Arial\',sans-serif; mso-fareast-font-family: Arial; color: black;\"><span style=\"mso-list: Ignore;\">&bull;<span style=\"font: 7.0pt \'Times New Roman\';\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span><!--[endif]--><span style=\"color: black;\">Poses a credible threat of harm</span></p>\r\n<p class=\"MsoNormal\" style=\"text-align: justify; line-height: 106%; margin: 0cm 0cm 0cm 73.85pt;\"><span style=\"color: black;\">&nbsp;</span></p>\r\n<p class=\"MsoNormal\" style=\"text-align: justify; line-height: 106%; margin: 0cm 0cm 0cm 72.0pt;\"><span style=\"color: black;\"><span style=\"mso-spacerun: yes;\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span>Personal cybercrime is perpetrated against individuals, as opposed to businesses and other organizations. These are crimes that affect you directly and that you need to be aware of. Cyberbullying and cyber-stalking are two categories of harassment. When the exchange involves two minors, it is cyberbullying; when it involves adults, it is cyber-harassment. Cyber-stalking is more serious in nature, with the stalker demonstrating a pattern of harassment and posing a credible threat of harm.</span></p>\r\n<p class=\"MsoNormal\" style=\"text-align: justify; line-height: 106%; margin: 0cm 0cm 0cm 72.0pt;\"><span style=\"color: black;\">&nbsp;</span></p>\r\n<p class=\"MsoNormal\" style=\"text-align: justify; text-indent: -18.0pt; line-height: 106%; mso-list: l0 level1 lfo3; margin: 0cm 0cm 0cm 73.85pt;\"><!-- [if !supportLists]--><span style=\"font-family: \'Noto Sans Symbols\'; mso-fareast-font-family: \'Noto Sans Symbols\'; mso-bidi-font-family: \'Noto Sans Symbols\'; color: black;\"><span style=\"mso-list: Ignore;\">▪<span style=\"font: 7.0pt \'Times New Roman\';\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span><!--[endif]--><strong style=\"mso-bidi-font-weight: normal;\"><span style=\"color: black;\">Phishing </span></strong></p>\r\n<p class=\"MsoNormal\" style=\"text-align: justify; text-indent: -18.0pt; line-height: 106%; mso-list: l1 level1 lfo6; margin: 0cm 0cm 0cm 73.85pt;\"><!-- [if !supportLists]--><span style=\"font-family: \'Noto Sans Symbols\'; mso-fareast-font-family: \'Noto Sans Symbols\'; mso-bidi-font-family: \'Noto Sans Symbols\'; color: black;\"><span style=\"mso-list: Ignore;\">✔<span style=\"font: 7.0pt \'Times New Roman\';\">&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span><!--[endif]--><span style=\"color: black;\">Email messages and IMs </span></p>\r\n<p class=\"MsoNormal\" style=\"text-align: justify; text-indent: -18.0pt; line-height: 106%; mso-list: l1 level1 lfo6; margin: 0cm 0cm 0cm 73.85pt;\"><!-- [if !supportLists]--><span style=\"font-family: \'Noto Sans Symbols\'; mso-fareast-font-family: \'Noto Sans Symbols\'; mso-bidi-font-family: \'Noto Sans Symbols\'; color: black;\"><span style=\"mso-list: Ignore;\">✔<span style=\"font: 7.0pt \'Times New Roman\';\">&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span><!--[endif]--><span style=\"color: black;\">Appear to be from someone with whom you do business</span></p>\r\n<p class=\"MsoNormal\" style=\"text-align: justify; line-height: 106%; margin: 0cm 0cm 0cm 72.0pt;\"><span style=\"color: black;\">Designed to trick you into providing usernames and passwords</span></p>\r\n<p class=\"MsoNormal\" style=\"text-align: justify; line-height: 106%; margin: 0cm 0cm 0cm 72.0pt;\">&nbsp;</p>\r\n<p class=\"MsoNormal\" style=\"text-align: justify; line-height: 106%; margin: 0cm 0cm 0cm 72.0pt;\"><span style=\"color: black;\"><img src=\"http://localhost/capstone/images/content/img_1763872251_4c6033213b8ffbc4.png\"></span></p>\r\n<p class=\"MsoNormal\" style=\"text-align: justify; line-height: 106%; margin: 0cm 0cm 0cm 72.0pt;\">&nbsp;</p>\r\n<p class=\"MsoNormal\" style=\"text-align: justify; line-height: 106%; margin: 0cm 0cm 0cm 72.0pt;\"><strong style=\"mso-bidi-font-weight: normal;\"><span style=\"color: black;\">Figure 1. A Phishing attack mimicking the email from Paypal</span></strong></p>\r\n<p class=\"MsoNormal\" style=\"text-align: center; line-height: 106%; margin: 0cm 0cm 0cm 72.0pt;\" align=\"center\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">(source: </span></em><a href=\"https://www.phishing.org/phishing-examples\"><em style=\"mso-bidi-font-style: normal;\">https://www.phishing.org/phishing-examples</em></a><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">)</span></em></p>\r\n<p class=\"MsoNormal\" style=\"text-indent: -18.0pt; line-height: 106%; mso-list: l0 level1 lfo1; margin: 0cm 0cm 0cm 73.85pt;\"><!-- [if !supportLists]--><span style=\"font-family: \'Noto Sans Symbols\'; mso-fareast-font-family: \'Noto Sans Symbols\'; mso-bidi-font-family: \'Noto Sans Symbols\'; color: black;\"><span style=\"mso-list: Ignore;\">▪<span style=\"font: 7.0pt \'Times New Roman\';\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span><!--[endif]--><strong style=\"mso-bidi-font-weight: normal;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">Pharming</span></em></strong></p>\r\n<p class=\"MsoNormal\" style=\"text-indent: -18.0pt; line-height: 106%; mso-list: l1 level1 lfo2; margin: 0cm 0cm 0cm 73.85pt;\"><!-- [if !supportLists]--><span style=\"font-family: \'Noto Sans Symbols\'; mso-fareast-font-family: \'Noto Sans Symbols\'; mso-bidi-font-family: \'Noto Sans Symbols\'; color: black;\"><span style=\"mso-list: Ignore;\">✔<span style=\"font: 7.0pt \'Times New Roman\';\">&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span><!--[endif]--><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">is a cyberattack intended to redirect a website\'s traffic to another, fake site. Pharming can be conducted either by changing the hosts file on a victim\'s computer or by exploitation of a vulnerability in DNS server software.</span></em></p>\r\n<p class=\"MsoNormal\" style=\"text-indent: -18.0pt; line-height: 106%; mso-list: l1 level1 lfo2; margin: 0cm 0cm 0cm 73.85pt;\"><!-- [if !supportLists]--><span style=\"font-family: \'Noto Sans Symbols\'; mso-fareast-font-family: \'Noto Sans Symbols\'; mso-bidi-font-family: \'Noto Sans Symbols\'; color: black;\"><span style=\"mso-list: Ignore;\">✔<span style=\"font: 7.0pt \'Times New Roman\';\">&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span><!--[endif]--><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">Redirects you to a phony website <strong style=\"mso-bidi-font-weight: normal;\">even if you type the URL</strong></span></em></p>\r\n<p class=\"MsoNormal\" style=\"text-indent: -18.0pt; line-height: 106%; mso-list: l1 level1 lfo2; margin: 0cm 0cm 0cm 73.85pt;\"><!-- [if !supportLists]--><span style=\"font-family: \'Noto Sans Symbols\'; mso-fareast-font-family: \'Noto Sans Symbols\'; mso-bidi-font-family: \'Noto Sans Symbols\'; color: black;\"><span style=\"mso-list: Ignore;\">✔<span style=\"font: 7.0pt \'Times New Roman\';\">&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span><!--[endif]--><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">Hijacks a company&rsquo;s domain name</span></em></p>\r\n<p class=\"MsoNormal\" style=\"text-indent: -18.0pt; line-height: 106%; mso-list: l1 level1 lfo2; margin: 0cm 0cm 0cm 73.85pt;\"><!-- [if !supportLists]--><span style=\"font-family: \'Noto Sans Symbols\'; mso-fareast-font-family: \'Noto Sans Symbols\'; mso-bidi-font-family: \'Noto Sans Symbols\'; color: black;\"><span style=\"mso-list: Ignore;\">✔<span style=\"font: 7.0pt \'Times New Roman\';\">&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span><!--[endif]--><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">Pharming has been called \"phishing without a lure.\"</span></em></p>\r\n<p class=\"MsoNormal\" style=\"margin-bottom: 0cm; text-align: justify; line-height: 106%;\"><img src=\"http://localhost/capstone/images/content/img_1763872348_2ca7ba8cbc98bb15.png\"></p>\r\n<p class=\"MsoNormal\" style=\"margin-bottom: 0cm; text-align: justify; line-height: 106%;\"><strong style=\"mso-bidi-font-weight: normal;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">Figure 2. A Pharming attack mimicking the Google search engine</span></em></strong></p>\r\n<p class=\"MsoNormal\" style=\"margin-bottom: 0cm; text-align: justify; line-height: 106%;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">(Source: </span></em><em style=\"mso-bidi-font-style: normal;\"><a href=\"https://wpree94800.weebly.com/section-6---effects-of-ict/pharming)\">https://wpree94800.weebly.com/section-6---effects-of-ict/pharming</a></em><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\"><a href=\"https://wpree94800.weebly.com/section-6---effects-of-ict/pharming)\">)</a></span></em></p>\r\n<p class=\"MsoNormal\" style=\"line-height: 106%; margin: 0cm 0cm 0cm 73.85pt;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\"><span style=\"mso-spacerun: yes;\">&nbsp; </span>In phishing, the perpetrator sends out legitimate-looking e-mails, appearing to come from some of the Web\'s most popular sites, in an effort to obtain personal and financial information from individual recipients. But in pharming, larger numbers of computer users can be victimized because it is not necessary to target individuals one by one and no conscious action is required on the part of the victim. In one form of pharming attack, code sent in an e-mail modifies local host files on a personal computer. The host files convert URLs into the number strings that the computer uses to access Web sites. A computer with a compromised host file will go to the fake Web site even if a user types in the correct Internet address or clicks on an affected bookmark entry. Some spyware removal programs can correct the corruption, but it frequently recurs unless the user changes browsing habits.</span></em></p>\r\n<p class=\"MsoNormal\" style=\"line-height: 106%; margin: 0cm 0cm 0cm 73.85pt;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">&nbsp;</span></em></p>\r\n<p class=\"MsoListParagraph\" style=\"mso-add-space: auto; text-indent: -18.0pt; line-height: 106%; mso-list: l1 level1 lfo1; margin: 0cm 0cm 0cm 73.85pt;\"><!-- [if !supportLists]--><strong style=\"mso-bidi-font-weight: normal;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"font-family: \'Times New Roman\',serif; mso-fareast-font-family: \'Times New Roman\'; color: black;\"><span style=\"mso-list: Ignore;\">1.<span style=\"font: 7.0pt \'Times New Roman\';\">&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span></em></strong><!--[endif]--><strong style=\"mso-bidi-font-weight: normal;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">Social Network Attacks</span></em></strong></p>\r\n<p class=\"MsoNormal\" style=\"line-height: 106%; margin: 0cm 0cm 0cm 73.85pt;\"><strong style=\"mso-bidi-font-weight: normal;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\"><span style=\"mso-spacerun: yes;\">&nbsp;</span></span></em></strong></p>\r\n<p class=\"MsoNormal\" style=\"text-indent: -18.0pt; line-height: 106%; mso-list: l3 level1 lfo2; margin: 0cm 0cm 0cm 73.85pt;\"><!-- [if !supportLists]--><span style=\"font-family: \'Noto Sans Symbols\'; mso-fareast-font-family: \'Noto Sans Symbols\'; mso-bidi-font-family: \'Noto Sans Symbols\'; color: black;\"><span style=\"mso-list: Ignore;\">▪<span style=\"font: 7.0pt \'Times New Roman\';\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span><!--[endif]--><strong style=\"mso-bidi-font-weight: normal;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">Adware</span></em></strong><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\"> - an unwanted software designed to throw advertisements up on the screen of the user&rsquo;s computer while browsing the social networking sites.</span></em></p>\r\n<p class=\"MsoNormal\" style=\"text-indent: -18.0pt; line-height: 106%; mso-list: l3 level1 lfo2; margin: 0cm 0cm 0cm 73.85pt;\"><!-- [if !supportLists]--><span style=\"font-family: \'Noto Sans Symbols\'; mso-fareast-font-family: \'Noto Sans Symbols\'; mso-bidi-font-family: \'Noto Sans Symbols\'; color: black;\"><span style=\"mso-list: Ignore;\">▪<span style=\"font: 7.0pt \'Times New Roman\';\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span><!--[endif]--><strong style=\"mso-bidi-font-weight: normal;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">Clickjacking </span></em></strong><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">- clicking on a link allows this malware to post unwanted links on the web page.</span></em></p>\r\n<p class=\"MsoNormal\" style=\"text-indent: -18.0pt; line-height: 106%; mso-list: l3 level1 lfo2; margin: 0cm 0cm 0cm 73.85pt;\"><!-- [if !supportLists]--><span style=\"font-family: \'Noto Sans Symbols\'; mso-fareast-font-family: \'Noto Sans Symbols\'; mso-bidi-font-family: \'Noto Sans Symbols\'; color: black;\"><span style=\"mso-list: Ignore;\">▪<span style=\"font: 7.0pt \'Times New Roman\';\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span><!--[endif]--><strong style=\"mso-bidi-font-weight: normal;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">Malicious script scams</span></em></strong><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\"> - copy and paste some text into your address bar, which executes a malicious script that creates pages and events or sends spam to your friends.</span></em></p>\r\n<p class=\"MsoNormal\" style=\"text-indent: -18.0pt; line-height: 106%; mso-list: l3 level1 lfo2; margin: 0cm 0cm 0cm 73.85pt;\"><!-- [if !supportLists]--><span style=\"font-family: \'Noto Sans Symbols\'; mso-fareast-font-family: \'Noto Sans Symbols\'; mso-bidi-font-family: \'Noto Sans Symbols\'; color: black;\"><span style=\"mso-list: Ignore;\">▪<span style=\"font: 7.0pt \'Times New Roman\';\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span><!--[endif]--><strong style=\"mso-bidi-font-weight: normal;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">Fraud </span></em></strong></p>\r\n<p class=\"MsoNormal\" style=\"text-indent: -18.0pt; line-height: 106%; mso-list: l0 level1 lfo3; margin: 0cm 0cm 0cm 73.85pt;\"><!-- [if !supportLists]--><span style=\"font-family: \'Noto Sans Symbols\'; mso-fareast-font-family: \'Noto Sans Symbols\'; mso-bidi-font-family: \'Noto Sans Symbols\'; color: black;\"><span style=\"mso-list: Ignore;\">✔<span style=\"font: 7.0pt \'Times New Roman\';\">&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span><!--[endif]--><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">could involve hiding of information or providing incorrect information for the purpose of tricking victims out of money, property, and inheritance.</span></em></p>\r\n<p class=\"MsoNormal\" style=\"text-indent: -18.0pt; line-height: 106%; mso-list: l0 level1 lfo3; margin: 0cm 0cm 0cm 73.85pt;\"><!-- [if !supportLists]--><span style=\"font-family: \'Noto Sans Symbols\'; mso-fareast-font-family: \'Noto Sans Symbols\'; mso-bidi-font-family: \'Noto Sans Symbols\'; color: black;\"><span style=\"mso-list: Ignore;\">✔<span style=\"font: 7.0pt \'Times New Roman\';\">&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span><!--[endif]--><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">schemes that convince you to give money or property to a person</span></em></p>\r\n<p class=\"MsoNormal\" style=\"text-indent: -18.0pt; line-height: 106%; mso-list: l2 level1 lfo4; margin: 0cm 0cm 0cm 137.6pt;\"><!-- [if !supportLists]--><span style=\"font-family: \'Noto Sans Symbols\'; mso-fareast-font-family: \'Noto Sans Symbols\'; mso-bidi-font-family: \'Noto Sans Symbols\'; color: black;\"><span style=\"mso-list: Ignore;\">▪<span style=\"font: 7.0pt \'Times New Roman\';\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span><!--[endif]--><strong style=\"mso-bidi-font-weight: normal;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">Shill bidding</span></em></strong><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\"> </span></em></p>\r\n<p class=\"MsoNormal\" style=\"text-indent: -18.0pt; line-height: 106%; mso-list: l0 level1 lfo3; margin: 0cm 0cm 0cm 73.85pt;\"><!-- [if !supportLists]--><span style=\"font-family: \'Noto Sans Symbols\'; mso-fareast-font-family: \'Noto Sans Symbols\'; mso-bidi-font-family: \'Noto Sans Symbols\'; color: black;\"><span style=\"mso-list: Ignore;\">✔<span style=\"font: 7.0pt \'Times New Roman\';\">&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span><!--[endif]--><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">fake bidding to drive up the price of an item</span></em></p>\r\n<p class=\"MsoNormal\" style=\"text-indent: -18.0pt; line-height: 106%; mso-list: l0 level1 lfo3; margin: 0cm 0cm 0cm 73.85pt;\">&nbsp;</p>\r\n<p class=\"MsoNormal\" style=\"text-indent: -18.0pt; line-height: 106%; mso-list: l0 level1 lfo3; margin: 0cm 0cm 0cm 73.85pt;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\"><img src=\"http://localhost/capstone/images/content/img_1763872445_9c00eaeea03dbdf4.png\"></span></em></p>\r\n<p class=\"MsoNormal\" style=\"text-indent: -18.0pt; line-height: 106%; mso-list: l0 level1 lfo3; margin: 0cm 0cm 0cm 73.85pt;\"><strong style=\"mso-bidi-font-weight: normal;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">Figure 3. Example of online fraud </span></em></strong><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">(Source: </span></em><em style=\"mso-bidi-font-style: normal;\"><a href=\"https://heimdalsecurity.com/blog/top-online-scams//)\">https://heimdalsecurity.com/blog/top-online-scams//</a></em><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\"><a href=\"https://heimdalsecurity.com/blog/top-online-scams//)\">)</a></span></em></p>\r\n<p class=\"MsoNormal\" style=\"text-indent: -18.0pt; line-height: 106%; mso-list: l1 level1 lfo1; margin: 0cm 0cm 0cm 137.6pt;\"><!-- [if !supportLists]--><span style=\"font-family: \'Noto Sans Symbols\'; mso-fareast-font-family: \'Noto Sans Symbols\'; mso-bidi-font-family: \'Noto Sans Symbols\'; color: black;\"><span style=\"mso-list: Ignore;\">▪<span style=\"font: 7.0pt \'Times New Roman\';\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span><!--[endif]--><strong style=\"mso-bidi-font-weight: normal;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">Identity Theft</span></em></strong></p>\r\n<p class=\"MsoNormal\" style=\"line-height: 106%; margin: 0cm 0cm 0cm 137.6pt;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">&nbsp;</span></em></p>\r\n<p class=\"MsoNormal\" style=\"text-indent: -18.0pt; line-height: 106%; mso-list: l3 level1 lfo2; margin: 0cm 0cm 0cm 73.85pt;\"><!-- [if !supportLists]--><span style=\"font-family: \'Noto Sans Symbols\'; mso-fareast-font-family: \'Noto Sans Symbols\'; mso-bidi-font-family: \'Noto Sans Symbols\'; color: black;\"><span style=\"mso-list: Ignore;\">✔<span style=\"font: 7.0pt \'Times New Roman\';\">&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span><!--[endif]--><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">the crime of obtaining the personal or financial information of another person for the sole purpose of assuming that person\'s name or identity to make transactions or purchases.</span></em></p>\r\n<p class=\"MsoNormal\" style=\"margin-bottom: 0cm; line-height: 106%;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\"><span style=\"mso-spacerun: yes;\">&nbsp;</span></span></em></p>\r\n<p class=\"MsoNormal\" style=\"margin-bottom: 0cm; line-height: 106%;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\"><span style=\"mso-spacerun: yes;\">&nbsp;</span></span></em></p>\r\n<p class=\"MsoNormal\" style=\"margin-bottom: 0cm; line-height: 106%;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\"><span style=\"mso-spacerun: yes;\">&nbsp;</span></span></em></p>\r\n<p class=\"MsoNormal\" style=\"margin-bottom: 0cm; line-height: 106%;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\"><span style=\"mso-spacerun: yes;\">&nbsp;</span></span></em></p>\r\n<p class=\"MsoNormal\" style=\"margin-bottom: 0cm; line-height: 106%;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\"><span style=\"mso-spacerun: yes;\">&nbsp;</span></span></em></p>\r\n<p class=\"MsoNormal\" style=\"text-indent: -18.0pt; line-height: 106%; mso-list: l0 level1 lfo3; margin: 0cm 0cm 0cm 73.85pt;\"><!-- [if !supportLists]--><strong style=\"mso-bidi-font-weight: normal;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"font-family: \'Times New Roman\',serif; mso-fareast-font-family: \'Times New Roman\'; color: black;\"><span style=\"mso-list: Ignore;\">1.<span style=\"font: 7.0pt \'Times New Roman\';\">&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span></em></strong><!--[endif]--><strong style=\"mso-bidi-font-weight: normal;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">Cybercrime Against the Government</span></em></strong></p>\r\n<p class=\"MsoNormal\" style=\"margin-bottom: 0cm; line-height: 106%;\"><strong style=\"mso-bidi-font-weight: normal;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\"><span style=\"mso-spacerun: yes;\">&nbsp;</span></span></em></strong></p>\r\n<ul style=\"margin-top: 0cm;\" type=\"circle\">\r\n<li class=\"MsoNormal\" style=\"color: black; margin-bottom: 0cm; line-height: 106%; mso-list: l2 level1 lfo4;\"><strong style=\"mso-bidi-font-weight: normal;\"><em style=\"mso-bidi-font-style: normal;\">Hacking - </em></strong><em style=\"mso-bidi-font-style: normal;\">is an attempt to exploit a computer system or a private network inside a computer. Simply put, it is the unauthorized access to or control over computer network security systems for some illicit purpose. A computer hacker is a computer expert who uses their technical knowledge to overcome a problem. While <strong style=\"mso-bidi-font-weight: normal;\">\"hacker\"</strong> can refer to any skilled computer programmer, the term has become associated in popular culture with a \"security hacker\", someone who, with their technical knowledge, uses bugs or exploits to break into computer systems. <strong style=\"mso-bidi-font-weight: normal;\">Hacktivism</strong> is the act of misusing a computer system or network for a socially or politically motivated reason. Individuals who perform hacktivism are known as <strong style=\"mso-bidi-font-weight: normal;\">hacktivists</strong>. On the other hand, <strong style=\"mso-bidi-font-weight: normal;\">data breach</strong> is when a sensitive data was stolen or viewed by someone unauthorized.</em></li>\r\n<li class=\"MsoNormal\" style=\"color: black; margin-bottom: 0cm; line-height: 106%; mso-list: l2 level1 lfo4;\"><em style=\"mso-bidi-font-style: normal;\">&nbsp;</em></li>\r\n</ul>\r\n<p class=\"MsoNormal\" style=\"margin-bottom: 0cm; line-height: 106%;\"><strong style=\"mso-bidi-font-weight: normal;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">3 Types of Hacking</span></em></strong></p>\r\n<p class=\"MsoNormal\" style=\"text-indent: -18.0pt; line-height: 106%; mso-list: l1 level1 lfo1; margin: 0cm 0cm 0cm 137.6pt;\"><!-- [if !supportLists]--><span style=\"font-family: \'Noto Sans Symbols\'; mso-fareast-font-family: \'Noto Sans Symbols\'; mso-bidi-font-family: \'Noto Sans Symbols\'; color: black;\"><span style=\"mso-list: Ignore;\">▪<span style=\"font: 7.0pt \'Times New Roman\';\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span><!--[endif]--><strong style=\"mso-bidi-font-weight: normal;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">White-hat or &ldquo;sneakers&rdquo;</span></em></strong><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\"> - Attempt to find security holes in a system to prevent future hacking</span></em></p>\r\n<p class=\"MsoNormal\" style=\"text-indent: -18.0pt; line-height: 106%; mso-list: l3 level1 lfo2; margin: 0cm 0cm 0cm 73.85pt;\"><!-- [if !supportLists]--><span style=\"font-family: \'Noto Sans Symbols\'; mso-fareast-font-family: \'Noto Sans Symbols\'; mso-bidi-font-family: \'Noto Sans Symbols\'; color: black;\"><span style=\"mso-list: Ignore;\">✔<span style=\"font: 7.0pt \'Times New Roman\';\">&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span><!--[endif]--><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">Also known as &ldquo;ethical hackers,&rdquo; they&rsquo;re often employed or contracted by companies and governmental entities, working as security specialists looking for vulnerabilities. While they employ the same methods as black hat hackers, they always have permission from the system&rsquo;s owner, making their actions completely legal. White hat hackers implement strategies like penetration tests, monitor in-place security systems, along with vulnerability assessments. Ethical hacking, the term used to describe the nature of a white hat hackers&rsquo; actions, can even be learned through independent sources, training, conferences, and certifications.</span></em></p>\r\n<p class=\"MsoNormal\" style=\"text-indent: -18.0pt; line-height: 106%; mso-list: l1 level1 lfo1; margin: 0cm 0cm 0cm 137.6pt;\"><!-- [if !supportLists]--><span style=\"font-family: \'Noto Sans Symbols\'; mso-fareast-font-family: \'Noto Sans Symbols\'; mso-bidi-font-family: \'Noto Sans Symbols\'; color: black;\"><span style=\"mso-list: Ignore;\">▪<span style=\"font: 7.0pt \'Times New Roman\';\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span><!--[endif]--><strong style=\"mso-bidi-font-weight: normal;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">Black-hat or &ldquo;crackers&rdquo;</span></em></strong><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\"> -Malicious intent such as theft and vandalism</span></em></p>\r\n<p class=\"MsoNormal\" style=\"text-indent: -18.0pt; line-height: 106%; mso-list: l3 level1 lfo2; margin: 0cm 0cm 0cm 73.85pt;\"><!-- [if !supportLists]--><span style=\"font-family: \'Noto Sans Symbols\'; mso-fareast-font-family: \'Noto Sans Symbols\'; mso-bidi-font-family: \'Noto Sans Symbols\'; color: black;\"><span style=\"mso-list: Ignore;\">✔<span style=\"font: 7.0pt \'Times New Roman\';\">&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span><!--[endif]--><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">Black hat hackers are normally responsible for creating malware, which is frequently used to infiltrate computerized networks and systems. They&rsquo;re usually motivated by personal or financial gain, but can also participate in espionage, protests, or merely enjoy the thrill. Black hat hackers can be anyone from amateurs to highly experienced and knowledgeable individuals looking to spread malware, steal private data, like login credentials, along with financial and personal information. Upon accessing their targets and depending on their motives, black hat hackers can either steal, manipulate, or destroy system data.</span></em></p>\r\n<p class=\"MsoNormal\" style=\"text-indent: -18.0pt; line-height: 106%; mso-list: l1 level1 lfo1; margin: 0cm 0cm 0cm 137.6pt;\"><!-- [if !supportLists]--><span style=\"font-family: \'Noto Sans Symbols\'; mso-fareast-font-family: \'Noto Sans Symbols\'; mso-bidi-font-family: \'Noto Sans Symbols\'; color: black;\"><span style=\"mso-list: Ignore;\">▪<span style=\"font: 7.0pt \'Times New Roman\';\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span><!--[endif]--><strong style=\"mso-bidi-font-weight: normal;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">Gray-hat</span></em></strong><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\"> - Illegal but not malicious intent</span></em></p>\r\n<p class=\"MsoNormal\" style=\"text-indent: -18.0pt; line-height: 106%; mso-list: l3 level1 lfo2; margin: 0cm 0cm 0cm 73.85pt;\"><!-- [if !supportLists]--><span style=\"font-family: \'Noto Sans Symbols\'; mso-fareast-font-family: \'Noto Sans Symbols\'; mso-bidi-font-family: \'Noto Sans Symbols\'; color: black;\"><span style=\"mso-list: Ignore;\">✔<span style=\"font: 7.0pt \'Times New Roman\';\">&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span><!--[endif]--><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">As the name suggests, these individuals utilize aspects from black and white hat hackers, but will usually seek out vulnerabilities in a system without an owner&rsquo;s permission or knowledge. While they&rsquo;ll report any issues, they encounter to the owner, they&rsquo;ll also request some sort of compensation or incentive. Should the owner not respond or reject their proposition, a grey hat hacker might exploit the newfound flaws. Grey hat hackers aren&rsquo;t malicious by nature, but do seek to have their efforts rewarded. Since grey hat hackers don&rsquo;t have permission to access the system by its owner, their actions are ultimately considered illegal, despite any alarming findings they might reveal. </span></em></p>\r\n<p class=\"MsoNormal\" style=\"text-indent: -18.0pt; line-height: 106%; mso-list: l3 level1 lfo2; margin: 0cm 0cm 0cm 73.85pt;\">&nbsp;</p>\r\n<p class=\"MsoNormal\" style=\"text-indent: -18.0pt; line-height: 106%; mso-list: l3 level1 lfo2; margin: 0cm 0cm 0cm 73.85pt;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\"><strong style=\"mso-bidi-font-weight: normal;\">Common Hacking Tools</strong></span></em></p>\r\n<p class=\"MsoNormal\" style=\"text-indent: -18.0pt; line-height: 106%; mso-list: l1 level1 lfo1; margin: 0cm 0cm 0cm 137.6pt;\"><!-- [if !supportLists]--><span style=\"font-family: \'Noto Sans Symbols\'; mso-fareast-font-family: \'Noto Sans Symbols\'; mso-bidi-font-family: \'Noto Sans Symbols\'; color: black;\"><span style=\"mso-list: Ignore;\">▪<span style=\"font: 7.0pt \'Times New Roman\';\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span><!--[endif]--><strong style=\"mso-bidi-font-weight: normal;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">Rootkits -</span></em></strong><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">a rootkit is a program or set of software tools that allow threat actors to gain remote access to control a computer system that interacts or connects with the internet. Originally, a rootkit was developed to open a backdoor in a system to fix specific software issues. Unfortunately, this program is now used by hackers to destabilize the control of an operating system from its legitimate operator or user.</span></em></p>\r\n<p class=\"MsoNormal\" style=\"margin-bottom: 0cm; line-height: 106%;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\"><span style=\"mso-spacerun: yes;\">&nbsp;</span></span></em></p>\r\n<p class=\"MsoNormal\" style=\"margin-bottom: 0cm; line-height: 106%;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">There are different ways to install rootkits in a victim&rsquo;s system, the most famous of them being social engineering and phishing attacks. Once rootkits are installed in the system, it secretly allows the hacker to access and control the system, giving them the opportunity to bring the system down or steal crucial data.</span></em></p>\r\n<p class=\"MsoNormal\" style=\"margin-bottom: 0cm; line-height: 106%;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\"><span style=\"mso-spacerun: yes;\">&nbsp;</span></span></em></p>\r\n<p class=\"MsoNormal\" style=\"text-indent: -18.0pt; line-height: 106%; mso-list: l1 level1 lfo1; margin: 0cm 0cm 0cm 137.6pt;\"><!-- [if !supportLists]--><span style=\"font-family: \'Noto Sans Symbols\'; mso-fareast-font-family: \'Noto Sans Symbols\'; mso-bidi-font-family: \'Noto Sans Symbols\'; color: black;\"><span style=\"mso-list: Ignore;\">▪<span style=\"font: 7.0pt \'Times New Roman\';\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span><!--[endif]--><strong style=\"mso-bidi-font-weight: normal;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">Keyloggers - </span></em></strong><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">a specially designed tool that logs or records every key pressed on a system. Keyloggers record every keystroke by clinging to the API (application programming interface) when typed through the computer keyboard. The recorded file then gets saved, which includes data like usernames, website visit details, screenshots, opened applications, etc.</span></em></p>\r\n<p class=\"MsoNormal\" style=\"margin-bottom: 0cm; line-height: 106%;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">Keyloggers can capture credit card numbers, personal messages, mobile numbers, passwords, and other details&ndash;&ndash;as long as they are typed. Normally, keyloggers arrive as malware that allows cybercriminals to steal sensitive data.</span></em></p>\r\n<p class=\"MsoNormal\" style=\"margin-bottom: 0cm; line-height: 106%;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\"><span style=\"mso-spacerun: yes;\">&nbsp;</span></span></em></p>\r\n<p class=\"MsoNormal\" style=\"text-indent: -18.0pt; line-height: 106%; mso-list: l1 level1 lfo1; margin: 0cm 0cm 0cm 137.6pt;\"><!-- [if !supportLists]--><span style=\"font-family: \'Noto Sans Symbols\'; mso-fareast-font-family: \'Noto Sans Symbols\'; mso-bidi-font-family: \'Noto Sans Symbols\'; color: black;\"><span style=\"mso-list: Ignore;\">▪<span style=\"font: 7.0pt \'Times New Roman\';\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span><!--[endif]--><strong style=\"mso-bidi-font-weight: normal;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">Vulnerability Scanner</span></em></strong><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\"> - classifies and detects various system weaknesses in networks, computers, communication systems, etc. This is one of the most common practices used by ethical hackers to find potential loopholes and fix them on an immediate basis. On the other hand, vulnerability scanners can also be used by black-hat hackers to check the system for potential weak spots in order to exploit the system.</span></em></p>\r\n<ul style=\"margin-top: 0cm;\" type=\"circle\">\r\n<li class=\"MsoNormal\" style=\"color: black; margin-bottom: 0cm; line-height: 106%; mso-list: l4 level1 lfo5;\"><strong style=\"mso-bidi-font-weight: normal;\"><em style=\"mso-bidi-font-style: normal;\">Cyberterrorism - </em></strong><em style=\"mso-bidi-font-style: normal;\"><span style=\"font-size: 12.0pt; line-height: 115%; font-family: \'Calibri\',sans-serif; mso-ascii-theme-font: minor-latin; mso-fareast-font-family: Calibri; mso-fareast-theme-font: minor-latin; mso-hansi-theme-font: minor-latin; mso-bidi-font-family: \'Times New Roman\'; mso-bidi-theme-font: minor-bidi; color: black; mso-ansi-language: EN-PH; mso-fareast-language: EN-US; mso-bidi-language: AR-SA;\">is the convergence of cyberspace and terrorism. It refers to unlawful attacks and threats of attacks against computers, networks and the information stored therein when done to intimidate or coerce a government or its people in furtherance of political or social objectives.</span></em></li>\r\n</ul>', 4, 0, 0);
INSERT INTO `module_sections` (`id`, `module_part_id`, `subtitle`, `content`, `section_order`, `has_quiz`, `order_index`) VALUES
(83, 57, 'Identifying and Preventing Malware Attacks', '<p class=\"MsoNormal\" style=\"margin-bottom: 0cm; line-height: 106%;\"><strong><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">Contents:</span></em></strong></p>\r\n<ul style=\"margin-top: 0cm;\" type=\"disc\">\r\n<li class=\"MsoNormal\" style=\"color: black; margin-bottom: 0cm; line-height: 106%; mso-list: l0 level1 lfo1; tab-stops: list 36.0pt;\"><strong><em style=\"mso-bidi-font-style: normal;\">Common Malware Types:</em></strong></li>\r\n<ul style=\"margin-top: 0cm;\" type=\"circle\">\r\n<li class=\"MsoNormal\" style=\"color: black; margin-bottom: 0cm; line-height: 106%; mso-list: l0 level2 lfo1; tab-stops: list 72.0pt;\"><em>Virus, Worms, Trojan Horse, Ransomware, Spyware, Adware, Logic Bomb, Rootkit, Botnet</em></li>\r\n</ul>\r\n<li class=\"MsoNormal\" style=\"color: black; margin-bottom: 0cm; line-height: 106%; mso-list: l0 level1 lfo1; tab-stops: list 36.0pt;\"><strong><em style=\"mso-bidi-font-style: normal;\">Real Example:</em></strong></li>\r\n<ul style=\"margin-top: 0cm;\" type=\"circle\">\r\n<li class=\"MsoNormal\" style=\"color: black; margin-bottom: 0cm; line-height: 106%; mso-list: l0 level2 lfo1; tab-stops: list 72.0pt;\"><em>&ldquo;I Love You&rdquo; Virus</em><em style=\"mso-bidi-font-style: normal;\"> &mdash; a Filipino-made worm that infected millions of computers worldwide.</em></li>\r\n</ul>\r\n<li class=\"MsoNormal\" style=\"color: black; margin-bottom: 0cm; line-height: 106%; mso-list: l0 level1 lfo1; tab-stops: list 36.0pt;\"><strong><em style=\"mso-bidi-font-style: normal;\">Effects:</em></strong></li>\r\n<ul style=\"margin-top: 0cm;\" type=\"circle\">\r\n<li class=\"MsoNormal\" style=\"color: black; margin-bottom: 0cm; line-height: 106%; mso-list: l0 level2 lfo1; tab-stops: list 72.0pt;\"><em style=\"mso-bidi-font-style: normal;\">Data loss, system damage, identity theft, or financial fraud.</em></li>\r\n</ul>\r\n</ul>\r\n<p style=\"color: black; margin-bottom: 0cm; line-height: 106%;\"><em style=\"mso-bidi-font-style: normal;\"><img src=\"http://localhost/capstone/images/content/img_1763872595_8a23a0b33cee64ce.png\"></em></p>\r\n<p class=\"MsoNormal\" style=\"margin-bottom: 0cm; text-align: center; line-height: 106%;\" align=\"center\"><strong style=\"mso-bidi-font-weight: normal;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">Figure </span></em></strong></p>\r\n<p class=\"MsoNormal\" style=\"margin-bottom: 0cm; text-align: center; line-height: 106%;\" align=\"center\"><strong style=\"mso-bidi-font-weight: normal;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">4. Spam messages in Gmail</span></em></strong></p>\r\n<p class=\"MsoNormal\" style=\"margin-bottom: 0cm; text-align: center; line-height: 106%;\" align=\"center\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">(source: <a href=\"https://emailmate.com/blog/2019/06/gmail-spam-filter-working/)\">https://emailmate.com/blog/2019/06/gmail-spam-filter-working/)</a></span></em></p>\r\n<ul style=\"margin-top: 0cm;\" type=\"circle\">\r\n<li class=\"MsoNormal\" style=\"color: black; margin-bottom: 0cm; line-height: 106%; mso-list: l1 level1 lfo1;\"><strong style=\"mso-bidi-font-weight: normal;\"><em style=\"mso-bidi-font-style: normal;\">Spam<span style=\"mso-spacerun: yes;\">&nbsp; </span></em></strong><em style=\"mso-bidi-font-style: normal;\">- a mass, unsolicited email. It is popular because it is easy and inexpensive to implement. Other forms include fax spam, IM spam, and text spam. The act of sending spam is called spamming.</em></li>\r\n</ul>\r\n<ul style=\"margin-top: 0cm;\" type=\"circle\">\r\n<li class=\"MsoNormal\" style=\"color: black; margin-bottom: 0cm; line-height: 106%; mso-list: l1 level1 lfo1;\"><strong style=\"mso-bidi-font-weight: normal;\"><em style=\"mso-bidi-font-style: normal;\">Cookies </em></strong><em style=\"mso-bidi-font-style: normal;\">- A cookie is a small text file that allows the website to recognize the user and personalize the site. Although they are useful, they could be used to collect information that you do not want to share.</em></li>\r\n</ul>\r\n<p class=\"MsoNormal\" style=\"text-indent: -18.0pt; line-height: 106%; mso-list: l0 level1 lfo2; margin: 0cm 0cm 0cm 73.85pt;\"><!-- [if !supportLists]--><span style=\"font-family: \'Noto Sans Symbols\'; mso-fareast-font-family: \'Noto Sans Symbols\'; mso-bidi-font-family: \'Noto Sans Symbols\'; color: black;\"><span style=\"mso-list: Ignore;\">✔<span style=\"font: 7.0pt \'Times New Roman\';\">&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span><!--[endif]--><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">Installed without your permission</span></em></p>\r\n<p class=\"MsoNormal\" style=\"text-indent: -18.0pt; line-height: 106%; mso-list: l0 level1 lfo2; margin: 0cm 0cm 0cm 73.85pt;\"><!-- [if !supportLists]--><span style=\"font-family: \'Noto Sans Symbols\'; mso-fareast-font-family: \'Noto Sans Symbols\'; mso-bidi-font-family: \'Noto Sans Symbols\'; color: black;\"><span style=\"mso-list: Ignore;\">✔<span style=\"font: 7.0pt \'Times New Roman\';\">&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span><!--[endif]--><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">Help websites identify you when you return</span></em></p>\r\n<p class=\"MsoNormal\" style=\"text-indent: -18.0pt; line-height: 106%; mso-list: l0 level1 lfo2; margin: 0cm 0cm 0cm 73.85pt;\"><!-- [if !supportLists]--><span style=\"font-family: \'Noto Sans Symbols\'; mso-fareast-font-family: \'Noto Sans Symbols\'; mso-bidi-font-family: \'Noto Sans Symbols\'; color: black;\"><span style=\"mso-list: Ignore;\">✔<span style=\"font: 7.0pt \'Times New Roman\';\">&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span><!--[endif]--><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">Track websites and pages you visit to better target ads</span></em></p>\r\n<p class=\"MsoNormal\" style=\"text-indent: -18.0pt; line-height: 106%; mso-list: l0 level1 lfo2; margin: 0cm 0cm 0cm 73.85pt;\"><!-- [if !supportLists]--><span style=\"font-family: \'Noto Sans Symbols\'; mso-fareast-font-family: \'Noto Sans Symbols\'; mso-bidi-font-family: \'Noto Sans Symbols\'; color: black;\"><span style=\"mso-list: Ignore;\">✔<span style=\"font: 7.0pt \'Times New Roman\';\">&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span><!--[endif]--><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">May collect information you don&rsquo;t want to share</span></em></p>\r\n<p class=\"MsoNormal\" style=\"margin-bottom: 0cm; line-height: 106%;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\"><span style=\"mso-spacerun: yes;\">&nbsp;</span></span></em></p>\r\n<ul style=\"margin-top: 0cm;\" type=\"circle\">\r\n<li class=\"MsoNormal\" style=\"color: black; margin-bottom: 0cm; line-height: 106%; mso-list: l1 level1 lfo1;\"><strong style=\"mso-bidi-font-weight: normal;\"><em style=\"mso-bidi-font-style: normal;\">Adware </em></strong><em style=\"mso-bidi-font-style: normal;\">- shows you ads, usually in the form of pop-ups or banner ads in websites and in software. Ads generate income for the software developer. When these ads use CPU cycles and Internet bandwidth, it can reduce PC performance.</em></li>\r\n</ul>\r\n<p style=\"color: black; margin-bottom: 0cm; line-height: 106%;\"><em style=\"mso-bidi-font-style: normal;\"><img src=\"http://localhost/capstone/images/content/img_1763872657_de395a22ea8e46a3.png\"></em></p>\r\n<p class=\"MsoNormal\" style=\"margin-bottom: 0cm; line-height: 106%;\"><strong style=\"mso-bidi-font-weight: normal;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\"><span style=\"mso-spacerun: yes;\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span>Figure 5. Adware</span></em></strong></p>\r\n<p class=\"MsoNormal\" style=\"margin-bottom: 0cm; line-height: 106%;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">(source: <a href=\"https://searchsecurity.techtarget.com/definition/adware)\">https://searchsecurity.techtarget.com/definition/adware)</a></span></em></p>\r\n<p class=\"MsoNormal\" style=\"margin-bottom: 0cm; line-height: 106%;\">&nbsp;</p>\r\n<ul style=\"margin-top: 0cm;\" type=\"circle\">\r\n<li class=\"MsoNormal\" style=\"color: black; margin-bottom: 0cm; line-height: 106%; mso-list: l0 level1 lfo1;\"><strong style=\"mso-bidi-font-weight: normal;\"><em style=\"mso-bidi-font-style: normal;\">Spyware </em></strong><em style=\"mso-bidi-font-style: normal;\">-<span style=\"mso-spacerun: yes;\">&nbsp; </span>Spyware is a type of malicious software (malware) that secretly monitors, records, and collects information from a user\'s device without their knowledge or permission. It is designed to run silently in the background.</em></li>\r\n</ul>\r\n<p class=\"MsoNormal\" style=\"margin-bottom: 0cm; line-height: 106%;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\"><span style=\"mso-spacerun: yes;\">&nbsp;</span></span></em></p>\r\n<ul style=\"margin-top: 0cm;\" type=\"circle\">\r\n<li class=\"MsoNormal\" style=\"color: black; margin-bottom: 0cm; line-height: 106%; mso-list: l0 level1 lfo1;\"><strong style=\"mso-bidi-font-weight: normal;\"><em style=\"mso-bidi-font-style: normal;\">Virus</em></strong><em style=\"mso-bidi-font-style: normal;\"> - is a program that replicates itself and infects computers. A computer virus needs a host file on which to travel, such as a game or email. The attack, also known as the payload, may corrupt or delete files, or it may even erase an entire disk. The virus uses the email program or game on the infected computer to send out copies of itself and infect other machines.</em></li>\r\n</ul>\r\n<p style=\"color: black; margin-bottom: 0cm; line-height: 106%;\"><em style=\"mso-bidi-font-style: normal;\"><img src=\"http://localhost/capstone/images/content/img_1763872703_8e123392ec17a029.png\"></em></p>\r\n<p class=\"MsoNormal\" style=\"margin-bottom: 0cm; line-height: 106%;\"><strong style=\"mso-bidi-font-weight: normal;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">Figure 6. I Love You virus created by Filipinos </span></em></strong></p>\r\n<p class=\"MsoNormal\" style=\"margin-bottom: 0cm; line-height: 106%;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">(source: </span></em><a href=\"https://infinitydatatel.com/computer-virus-protection-tips/)\"><em style=\"mso-bidi-font-style: normal;\">https://infinitydatatel.com/computer-virus-protection-tips/</em><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">)</span></em></a></p>\r\n<p class=\"MsoNormal\" style=\"margin-bottom: 0cm; line-height: 106%;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\"><span style=\"mso-spacerun: yes;\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span>The I love you computer virus emerged in May of 2000 and was a computer worm that infected millions of computers worldwide (approximately 10% of all computers were infected with the virus). U.S. governments had to take their messaging platforms off of the internet and maintain them locally to prevent infection of the virus. The Filipino creators used social engineering at its worst to send the file, which posed as a text file, but Windows had a vulnerability that did not show that it was, in fact, an *.EXE file. It would then email every single person on a user&rsquo;s contact list and overwrite numerous files with copies of itself, destroying computer systems.</span></em></p>\r\n<p class=\"MsoNormal\" style=\"margin-bottom: 0cm; line-height: 106%;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\"><span style=\"mso-spacerun: yes;\">&nbsp;</span></span></em></p>\r\n<ul style=\"margin-top: 0cm;\" type=\"circle\">\r\n<li class=\"MsoNormal\" style=\"color: black; margin-bottom: 0cm; line-height: 106%; mso-list: l2 level1 lfo1;\"><strong style=\"mso-bidi-font-weight: normal;\"><em style=\"mso-bidi-font-style: normal;\">Logic Bomb </em></strong></li>\r\n</ul>\r\n<p class=\"MsoNormal\" style=\"text-indent: -18.0pt; line-height: 106%; mso-list: l1 level1 lfo2; margin: 0cm 0cm 0cm 102.2pt;\"><!-- [if !supportLists]--><span style=\"font-family: \'Noto Sans Symbols\'; mso-fareast-font-family: \'Noto Sans Symbols\'; mso-bidi-font-family: \'Noto Sans Symbols\'; color: black;\"><span style=\"mso-list: Ignore;\">✔<span style=\"font: 7.0pt \'Times New Roman\';\">&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span><!--[endif]--><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">Behaves like a virus </span></em></p>\r\n<p class=\"MsoNormal\" style=\"text-indent: -18.0pt; line-height: 106%; mso-list: l1 level1 lfo2; margin: 0cm 0cm 0cm 102.2pt;\"><!-- [if !supportLists]--><span style=\"font-family: \'Noto Sans Symbols\'; mso-fareast-font-family: \'Noto Sans Symbols\'; mso-bidi-font-family: \'Noto Sans Symbols\'; color: black;\"><span style=\"mso-list: Ignore;\">✔<span style=\"font: 7.0pt \'Times New Roman\';\">&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span><!--[endif]--><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">Performs malicious act</span></em></p>\r\n<p class=\"MsoNormal\" style=\"text-indent: -18.0pt; line-height: 106%; mso-list: l1 level1 lfo2; margin: 0cm 0cm 0cm 102.2pt;\"><!-- [if !supportLists]--><span style=\"font-family: \'Noto Sans Symbols\'; mso-fareast-font-family: \'Noto Sans Symbols\'; mso-bidi-font-family: \'Noto Sans Symbols\'; color: black;\"><span style=\"mso-list: Ignore;\">✔<span style=\"font: 7.0pt \'Times New Roman\';\">&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span><!--[endif]--><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">Does not replicate</span></em></p>\r\n<p class=\"MsoNormal\" style=\"text-indent: -18.0pt; line-height: 106%; mso-list: l1 level1 lfo2; margin: 0cm 0cm 0cm 102.2pt;\"><!-- [if !supportLists]--><span style=\"font-family: \'Noto Sans Symbols\'; mso-fareast-font-family: \'Noto Sans Symbols\'; mso-bidi-font-family: \'Noto Sans Symbols\'; color: black;\"><span style=\"mso-list: Ignore;\">✔<span style=\"font: 7.0pt \'Times New Roman\';\">&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span><!--[endif]--><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">Attacks when <strong style=\"mso-bidi-font-weight: normal;\">certain conditions are met</strong></span></em></p>\r\n<p class=\"MsoNormal\" style=\"margin-bottom: 0cm; line-height: 106%;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\"><span style=\"mso-spacerun: yes;\">&nbsp;</span></span></em></p>\r\n<ul style=\"margin-top: 0cm;\" type=\"circle\">\r\n<li class=\"MsoNormal\" style=\"color: black; margin-bottom: 0cm; line-height: 106%; mso-list: l2 level1 lfo1;\"><strong style=\"mso-bidi-font-weight: normal;\"><em style=\"mso-bidi-font-style: normal;\">Time Bomb</em></strong></li>\r\n</ul>\r\n<p class=\"MsoNormal\" style=\"text-indent: -18.0pt; line-height: 106%; mso-list: l3 level1 lfo3; margin: 0cm 0cm 0cm 36.0pt;\"><!-- [if !supportLists]--><span style=\"font-family: \'Noto Sans Symbols\'; mso-fareast-font-family: \'Noto Sans Symbols\'; mso-bidi-font-family: \'Noto Sans Symbols\'; color: black;\"><span style=\"mso-list: Ignore;\">✔<span style=\"font: 7.0pt \'Times New Roman\';\">&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span><!--[endif]--><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">A logic bomb with a trigger that is a specific time or date</span></em></p>\r\n<p class=\"MsoNormal\" style=\"text-indent: -18.0pt; line-height: 106%; mso-list: l0 level1 lfo4; margin: 0cm 0cm 0cm 36.0pt;\"><!-- [if !supportLists]--><span style=\"font-family: \'Arial\',sans-serif; mso-fareast-font-family: Arial; color: black;\"><span style=\"mso-list: Ignore;\">&bull;<span style=\"font: 7.0pt \'Times New Roman\';\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span><!--[endif]--><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">April Fool&rsquo;s Day</span></em></p>\r\n<p class=\"MsoNormal\" style=\"text-indent: -18.0pt; line-height: 106%; mso-list: l0 level1 lfo4; margin: 0cm 0cm 0cm 36.0pt;\"><!-- [if !supportLists]--><span style=\"font-family: \'Arial\',sans-serif; mso-fareast-font-family: Arial; color: black;\"><span style=\"mso-list: Ignore;\">&bull;<span style=\"font: 7.0pt \'Times New Roman\';\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span><!--[endif]--><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">Friday the 13<sup>th</sup></span></em></p>\r\n<p class=\"MsoNormal\" style=\"line-height: 106%; margin: 0cm 0cm 0cm 36.0pt;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">&nbsp;</span></em></p>\r\n<ul style=\"margin-top: 0cm;\" type=\"circle\">\r\n<li class=\"MsoNormal\" style=\"color: black; margin-bottom: 0cm; line-height: 106%; mso-list: l2 level1 lfo1;\"><strong style=\"mso-bidi-font-weight: normal;\"><em style=\"mso-bidi-font-style: normal;\">Worms </em></strong></li>\r\n</ul>\r\n<p class=\"MsoNormal\" style=\"text-indent: -18.0pt; line-height: 106%; mso-list: l3 level1 lfo3; margin: 0cm 0cm 0cm 36.0pt;\"><!-- [if !supportLists]--><span style=\"font-family: \'Noto Sans Symbols\'; mso-fareast-font-family: \'Noto Sans Symbols\'; mso-bidi-font-family: \'Noto Sans Symbols\'; color: black;\"><span style=\"mso-list: Ignore;\">✔<span style=\"font: 7.0pt \'Times New Roman\';\">&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span><!--[endif]--><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">Self-replicating</span></em></p>\r\n<p class=\"MsoNormal\" style=\"text-indent: -18.0pt; line-height: 106%; mso-list: l3 level1 lfo3; margin: 0cm 0cm 0cm 36.0pt;\"><!-- [if !supportLists]--><span style=\"font-family: \'Noto Sans Symbols\'; mso-fareast-font-family: \'Noto Sans Symbols\'; mso-bidi-font-family: \'Noto Sans Symbols\'; color: black;\"><span style=\"mso-list: Ignore;\">✔<span style=\"font: 7.0pt \'Times New Roman\';\">&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span><!--[endif]--><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">Do not need a host to travel</span></em></p>\r\n<p class=\"MsoNormal\" style=\"text-indent: -18.0pt; line-height: 106%; mso-list: l3 level1 lfo3; margin: 0cm 0cm 0cm 36.0pt;\"><!-- [if !supportLists]--><span style=\"font-family: \'Noto Sans Symbols\'; mso-fareast-font-family: \'Noto Sans Symbols\'; mso-bidi-font-family: \'Noto Sans Symbols\'; color: black;\"><span style=\"mso-list: Ignore;\">✔<span style=\"font: 7.0pt \'Times New Roman\';\">&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span><!--[endif]--><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">Travel over networks to infect other machines</span></em></p>\r\n<p class=\"MsoNormal\" style=\"text-indent: -18.0pt; line-height: 106%; mso-list: l3 level1 lfo3; margin: 0cm 0cm 0cm 36.0pt;\"><!-- [if !supportLists]--><span style=\"font-family: \'Noto Sans Symbols\'; mso-fareast-font-family: \'Noto Sans Symbols\'; mso-bidi-font-family: \'Noto Sans Symbols\'; color: black;\"><span style=\"mso-list: Ignore;\">✔<span style=\"font: 7.0pt \'Times New Roman\';\">&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span><!--[endif]--><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">very dangerous as they take up a lot of bandwidth and other valuable resources.</span></em></p>\r\n<p class=\"MsoNormal\" style=\"line-height: 106%; margin: 0cm 0cm 0cm 36.0pt;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">&nbsp;</span></em></p>\r\n<p class=\"MsoNormal\" style=\"margin-bottom: 0cm; line-height: 106%;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\"><span style=\"mso-spacerun: yes;\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span>There is no universal classification of computer worms, but they can be organized into types based on how they are distributed between computers. The five common types are as follows:</span></em></p>\r\n<p class=\"MsoNormal\" style=\"margin-bottom: 0cm; line-height: 106%;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">&nbsp;</span></em></p>\r\n<p class=\"MsoNormal\" style=\"margin-bottom: 0cm; line-height: 106%;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">1. Internet Worms</span></em></p>\r\n<p class=\"MsoNormal\" style=\"margin-bottom: 0cm; line-height: 106%;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\"><span style=\"mso-spacerun: yes;\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span>Like they do with computer networks, computer worms also target popular websites with insufficient security. When they manage to infect the site, internet worms can replicate themselves onto any computer being used to access the website in question. From there, internet worms are distributed to other connected computers through the internet and local area network connections.</span></em></p>\r\n<p class=\"MsoNormal\" style=\"margin-bottom: 0cm; line-height: 106%;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">2. Email Worms</span></em></p>\r\n<p class=\"MsoNormal\" style=\"margin-bottom: 0cm; line-height: 106%;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\"><span style=\"mso-spacerun: yes;\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span>Email worms are most often distributed via compromised email attachments. They usually have double extensions (for example, .mp4.exe or .avi.exe) so that the recipient would think that they are media files and not malicious computer programs. When the victims click on the attachment, copies of the same infected file will automatically be sent to addresses from their contacts list.</span></em></p>\r\n<p class=\"MsoNormal\" style=\"margin-bottom: 0cm; line-height: 106%;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\"><span style=\"mso-spacerun: yes;\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span>An email message doesn&rsquo;t have to contain a downloadable attachment to distribute a computer worm. Instead, the body of the message might contain a link that&rsquo;s shortened so that the recipient can&rsquo;t tell what it&rsquo;s about without clicking on it. When they click on the link, they will be taken to an infected website that will automatically start downloading malicious software to their computer.</span></em></p>\r\n<p class=\"MsoNormal\" style=\"margin-bottom: 0cm; line-height: 106%;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">3. Instant Messaging Worms</span></em></p>\r\n<p class=\"MsoNormal\" style=\"margin-bottom: 0cm; line-height: 106%;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\"><span style=\"mso-spacerun: yes;\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span>Instant messaging worms are exactly the same as email worms, the only difference being their method of distribution. Once again, they are masked as attachments or clickable links to websites. They are often accompanied by short messages like &ldquo;LOL&rdquo; or &ldquo;You have to see this!&rdquo; to trick the victim into thinking that their friend is sending them a funny video to look at.</span></em></p>\r\n<p class=\"MsoNormal\" style=\"margin-bottom: 0cm; line-height: 106%;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\"><span style=\"mso-spacerun: yes;\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span>When the user clicks on the link or the attachment &ndash; be it in Messenger, WhatsApp, Skype, or any other popular messaging app &ndash; the exact same message will then be sent to their contacts. Unless the worm has replicated itself onto their computer, users can solve this problem by changing their password.</span></em></p>\r\n<p class=\"MsoNormal\" style=\"margin-bottom: 0cm; line-height: 106%;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">4. File-Sharing Worms</span></em></p>\r\n<p class=\"MsoNormal\" style=\"margin-bottom: 0cm; line-height: 106%;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\"><span style=\"mso-spacerun: yes;\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span>Although illegal, file-sharing and peer-to-peer file transfers are still used by millions of people around the world. Doing so, they are unknowingly exposing their computers to the threat of file-sharing worms. Like email and instant messaging worms, these programs are disguised as media files with dual extensions.</span></em></p>\r\n<p class=\"MsoNormal\" style=\"margin-bottom: 0cm; line-height: 106%;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\"><span style=\"mso-spacerun: yes;\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span>When the victim opens the downloaded file to view it or listen to it, they will download the worm to their computer. Even if it seems that users have downloaded an actual playable media file, an executable malicious file could be hidden in the folder and discreetly installed when the media file is first opened.</span></em></p>\r\n<p class=\"MsoNormal\" style=\"margin-bottom: 0cm; line-height: 106%;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">&nbsp;</span></em></p>\r\n<p class=\"MsoNormal\" style=\"margin-bottom: 0cm; line-height: 106%;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">5. IRC Worms</span></em></p>\r\n<p class=\"MsoNormal\" style=\"margin-bottom: 0cm; line-height: 106%;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\"><span style=\"mso-spacerun: yes;\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span>Internet Relay Chat (IRC) is a messaging app that is mostly outdated nowadays but was all the rage at the turn of the century. Same as with today&rsquo;s instant messaging platforms, computer worms were distributed via messages containing links and attachments. The latter was less effective due to an extra layer of protection that prompted users to accept incoming files before any transfer could take place.</span></em></p>\r\n<p class=\"MsoNormal\" style=\"margin-bottom: 0cm; line-height: 106%;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">&nbsp;</span></em></p>\r\n<ul style=\"margin-top: 0cm;\" type=\"circle\">\r\n<li class=\"MsoNormal\" style=\"color: black; margin-bottom: 0cm; line-height: 106%; mso-list: l2 level1 lfo1;\"><strong style=\"mso-bidi-font-weight: normal;\"><em style=\"mso-bidi-font-style: normal;\">Botnet </em></strong><em style=\"mso-bidi-font-style: normal;\">- A botnet is a network of computer zombies or bots controlled by a master. Fake security notifications are the most common way to spread bots. A botnet could launch a denial-of-service attack, which cripples a server or network by sending out excessive traffic.</em></li>\r\n</ul>\r\n<p class=\"MsoNormal\" style=\"margin-bottom: 0cm; line-height: 106%;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\"><span style=\"mso-spacerun: yes;\">&nbsp;</span></span></em></p>\r\n<ul style=\"margin-top: 0cm;\" type=\"circle\">\r\n<li class=\"MsoNormal\" style=\"color: black; margin-bottom: 0cm; line-height: 106%; mso-list: l2 level1 lfo1;\"><strong style=\"mso-bidi-font-weight: normal;\"><em style=\"mso-bidi-font-style: normal;\">Trojan Horse</em></strong><em style=\"mso-bidi-font-style: normal;\"> - A Trojan horse, or Trojan, is a program that appears to be legitimate but is actually malicious. Trojans might install adware, a toolbar, or a keylogger, or open a backdoor.</em></li>\r\n</ul>\r\n<p class=\"MsoNormal\" style=\"margin-bottom: 0cm; line-height: 106%;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\"><span style=\"mso-spacerun: yes;\">&nbsp;</span></span></em></p>\r\n<ul style=\"margin-top: 0cm;\" type=\"circle\">\r\n<li class=\"MsoNormal\" style=\"color: black; margin-bottom: 0cm; line-height: 106%; mso-list: l2 level1 lfo1;\"><strong style=\"mso-bidi-font-weight: normal;\"><em style=\"mso-bidi-font-style: normal;\">Ransomware </em></strong><em style=\"mso-bidi-font-style: normal;\">- Ransomware is malware that prevents you from using your computer until you pay a fee. Payment is usually requested in bitcoin, an anonymous, digital, encrypted currency.</em></li>\r\n</ul>\r\n<p class=\"MsoNormal\" style=\"margin-bottom: 0cm; line-height: 106%;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\"><span style=\"mso-spacerun: yes;\">&nbsp;</span></span></em></p>\r\n<ul style=\"margin-top: 0cm;\" type=\"circle\">\r\n<li class=\"MsoNormal\" style=\"color: black; margin-bottom: 0cm; line-height: 106%; mso-list: l2 level1 lfo1;\"><strong style=\"mso-bidi-font-weight: normal;\"><em style=\"mso-bidi-font-style: normal;\">Rootkit </em></strong><em style=\"mso-bidi-font-style: normal;\">- A rootkit is a set of programs that allows someone to gain control over a computer system while hiding the fact that the computer has been compromised. A rootkit is almost impossible to detect. It allows the machine to become further infected by masking behavior of other malware.</em></li>\r\n</ul>', 5, 0, 0),
(84, 57, 'Building Digital Defenses', '<ul style=\"margin-top: 0cm;\" type=\"disc\">\r\n<li class=\"MsoNormal\" style=\"color: black; margin-bottom: 0cm; line-height: 106%; mso-list: l1 level1 lfo1; tab-stops: list 36.0pt;\"><strong><em style=\"mso-bidi-font-style: normal;\">Software Shields:</em></strong></li>\r\n<ul style=\"margin-top: 0cm;\" type=\"circle\">\r\n<li class=\"MsoNormal\" style=\"color: black; margin-bottom: 0cm; line-height: 106%; mso-list: l1 level2 lfo1; tab-stops: list 72.0pt;\"><em>Firewalls</em><em style=\"mso-bidi-font-style: normal;\"> &ndash; block unauthorized access</em></li>\r\n<li class=\"MsoNormal\" style=\"color: black; margin-bottom: 0cm; line-height: 106%; mso-list: l1 level2 lfo1; tab-stops: list 72.0pt;\"><em>Antivirus</em><em style=\"mso-bidi-font-style: normal;\"> and <span style=\"mso-bidi-font-style: italic;\">Antispyware</span> &ndash; detect and remove threats</em></li>\r\n<li class=\"MsoNormal\" style=\"color: black; margin-bottom: 0cm; line-height: 106%; mso-list: l1 level2 lfo1; tab-stops: list 72.0pt;\"><em>Security Suites</em><em style=\"mso-bidi-font-style: normal;\"> &ndash; combined protection tools</em></li>\r\n</ul>\r\n<li class=\"MsoNormal\" style=\"color: black; margin-bottom: 0cm; line-height: 106%; mso-list: l1 level1 lfo1; tab-stops: list 36.0pt;\"><strong><em style=\"mso-bidi-font-style: normal;\">Hardware Shields:</em></strong></li>\r\n<ul style=\"margin-top: 0cm;\" type=\"circle\">\r\n<li class=\"MsoNormal\" style=\"color: black; margin-bottom: 0cm; line-height: 106%; mso-list: l1 level2 lfo1; tab-stops: list 72.0pt;\"><em>Routers</em><em style=\"mso-bidi-font-style: normal;\"> and <span style=\"mso-bidi-font-style: italic;\">Network Address Translation (NAT)</span> for internet isolation</em></li>\r\n<li class=\"MsoNormal\" style=\"color: black; margin-bottom: 0cm; line-height: 106%; mso-list: l1 level2 lfo1; tab-stops: list 72.0pt;\"><em>Wireless Encryption</em><em style=\"mso-bidi-font-style: normal;\"> for Wi-Fi protection</em></li>\r\n</ul>\r\n<li class=\"MsoNormal\" style=\"color: black; margin-bottom: 0cm; line-height: 106%; mso-list: l1 level1 lfo1; tab-stops: list 36.0pt;\"><strong><em style=\"mso-bidi-font-style: normal;\">Operating System Shields:</em></strong></li>\r\n<ul style=\"margin-top: 0cm;\" type=\"circle\">\r\n<li class=\"MsoNormal\" style=\"color: black; margin-bottom: 0cm; line-height: 106%; mso-list: l1 level2 lfo1; tab-stops: list 72.0pt;\"><em style=\"mso-bidi-font-style: normal;\">Regular updates and patches to fix vulnerabilities</em></li>\r\n<li class=\"MsoNormal\" style=\"color: black; margin-bottom: 0cm; line-height: 106%; mso-list: l1 level2 lfo1; tab-stops: list 72.0pt;\"><em style=\"mso-bidi-font-style: normal;\">Automatic updates as part of proactive protection</em></li>\r\n</ul>\r\n</ul>\r\n<p class=\"MsoNormal\" style=\"line-height: 106%; margin: 0cm 0cm 0cm 36.0pt;\"><!-- [if !supportLists]--><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">How to Secure a Computer?</span></em></p>\r\n<p class=\"MsoNormal\" style=\"line-height: 106%; margin: 0cm 0cm 0cm 36.0pt;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\"><span style=\"mso-spacerun: yes;\">&nbsp;</span></span></em></p>\r\n<p class=\"MsoNormal\" style=\"line-height: 106%; margin: 0cm 0cm 0cm 36.0pt;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\"><span style=\"mso-spacerun: yes;\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span>One of the most common ways to get a malware infection on a computer is by downloading it. This could happen in a drive-by download. A <strong style=\"mso-bidi-font-weight: normal;\">drive-by download</strong> occurs when you visit a website that installs a program in the background without your knowledge.</span></em></p>\r\n<p class=\"MsoNormal\" style=\"line-height: 106%; margin: 0cm 0cm 0cm 36.0pt;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\"><span style=\"mso-spacerun: yes;\">&nbsp;</span></span></em></p>\r\n<p class=\"MsoNormal\" style=\"line-height: 106%; margin: 0cm 0cm 0cm 36.0pt;\"><strong style=\"mso-bidi-font-weight: normal;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">Shields Up: Software</span></em></strong></p>\r\n<p class=\"MsoNormal\" style=\"line-height: 106%; margin: 0cm 0cm 0cm 36.0pt;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\"><span style=\"mso-spacerun: yes;\">&nbsp;</span></span></em></p>\r\n<ul style=\"margin-top: 0cm;\" type=\"circle\">\r\n<li class=\"MsoNormal\" style=\"color: black; margin-bottom: 0cm; line-height: 106%; mso-list: l0 level1 lfo3;\"><strong style=\"mso-bidi-font-weight: normal;\"><em style=\"mso-bidi-font-style: normal;\">Firewall</em></strong></li>\r\n</ul>\r\n<p><em style=\"mso-bidi-font-style: normal;\"><span style=\"font-size: 12.0pt; line-height: 115%; font-family: \'Calibri\',sans-serif; mso-ascii-theme-font: minor-latin; mso-fareast-font-family: Calibri; mso-fareast-theme-font: minor-latin; mso-hansi-theme-font: minor-latin; mso-bidi-font-family: \'Times New Roman\'; mso-bidi-theme-font: minor-bidi; color: black; mso-ansi-language: EN-PH; mso-fareast-language: EN-US; mso-bidi-language: AR-SA;\">A firewall is designed to block unauthorized access to your network, but a software firewall blocks access to an individual machine.</span></em></p>\r\n<p><em style=\"mso-bidi-font-style: normal;\"><span style=\"font-size: 12.0pt; line-height: 115%; font-family: \'Calibri\',sans-serif; mso-ascii-theme-font: minor-latin; mso-fareast-font-family: Calibri; mso-fareast-theme-font: minor-latin; mso-hansi-theme-font: minor-latin; mso-bidi-font-family: \'Times New Roman\'; mso-bidi-theme-font: minor-bidi; color: black; mso-ansi-language: EN-PH; mso-fareast-language: EN-US; mso-bidi-language: AR-SA;\"><img src=\"http://localhost/capstone/images/content/img_1763872802_9cbde400020c2fc6.png\"></span></em></p>\r\n<ul style=\"margin-top: 0cm;\" type=\"circle\">\r\n<li class=\"MsoNormal\" style=\"color: black; margin-bottom: 0cm; line-height: 106%; mso-list: l1 level1 lfo1;\"><strong style=\"mso-bidi-font-weight: normal;\"><em style=\"mso-bidi-font-style: normal;\">Anti-virus program </em></strong></li>\r\n</ul>\r\n<p class=\"MsoNormal\" style=\"text-indent: -18.0pt; line-height: 106%; mso-list: l0 level1 lfo2; margin: 0cm 0cm 0cm 137.6pt;\"><!-- [if !supportLists]--><span style=\"mso-ascii-font-family: Calibri; mso-fareast-font-family: Calibri; mso-hansi-font-family: Calibri; mso-bidi-font-family: Calibri; color: black;\"><span style=\"mso-list: Ignore;\">-<span style=\"font: 7.0pt \'Times New Roman\';\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span><!--[endif]--><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">Protects against viruses, Trojans, worms, spyware</span></em></p>\r\n<ul style=\"margin-top: 0cm;\" type=\"circle\">\r\n<li class=\"MsoNormal\" style=\"color: black; margin-bottom: 0cm; line-height: 106%; mso-list: l1 level1 lfo1;\"><strong style=\"mso-bidi-font-weight: normal;\"><em style=\"mso-bidi-font-style: normal;\">Antispyware program</em></strong></li>\r\n</ul>\r\n<p class=\"MsoNormal\" style=\"text-indent: -18.0pt; line-height: 106%; mso-list: l0 level1 lfo2; margin: 0cm 0cm 0cm 137.6pt;\"><!-- [if !supportLists]--><span style=\"mso-ascii-font-family: Calibri; mso-fareast-font-family: Calibri; mso-hansi-font-family: Calibri; mso-bidi-font-family: Calibri; color: black;\"><span style=\"mso-list: Ignore;\">-<span style=\"font: 7.0pt \'Times New Roman\';\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span><!--[endif]--><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">Prevents adware and spyware from installing</span></em></p>\r\n<ul style=\"margin-top: 0cm;\" type=\"circle\">\r\n<li class=\"MsoNormal\" style=\"color: black; margin-bottom: 0cm; line-height: 106%; mso-list: l1 level1 lfo1;\"><strong style=\"mso-bidi-font-weight: normal;\"><em style=\"mso-bidi-font-style: normal;\">Security suite</em></strong></li>\r\n</ul>\r\n<p class=\"MsoNormal\" style=\"text-indent: -18.0pt; line-height: 106%; mso-list: l0 level1 lfo2; margin: 0cm 0cm 0cm 137.6pt;\"><!-- [if !supportLists]--><span style=\"mso-ascii-font-family: Calibri; mso-fareast-font-family: Calibri; mso-hansi-font-family: Calibri; mso-bidi-font-family: Calibri; color: black;\"><span style=\"mso-list: Ignore;\">-<span style=\"font: 7.0pt \'Times New Roman\';\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span><!--[endif]--><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">Package of security software</span></em></p>\r\n<p class=\"MsoNormal\" style=\"text-indent: -18.0pt; line-height: 106%; mso-list: l0 level1 lfo2; margin: 0cm 0cm 0cm 137.6pt;\"><!-- [if !supportLists]--><span style=\"mso-ascii-font-family: Calibri; mso-fareast-font-family: Calibri; mso-hansi-font-family: Calibri; mso-bidi-font-family: Calibri; color: black;\"><span style=\"mso-list: Ignore;\">-<span style=\"font: 7.0pt \'Times New Roman\';\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span><!--[endif]--><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">Combination of features</span></em></p>\r\n<p class=\"MsoNormal\" style=\"line-height: 106%; margin: 0cm 0cm 0cm 36.0pt;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\"><span style=\"mso-spacerun: yes;\">&nbsp;</span></span></em></p>\r\n<p class=\"MsoNormal\" style=\"line-height: 106%; margin: 0cm 0cm 0cm 36.0pt;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\"><span style=\"mso-spacerun: yes;\">&nbsp;</span></span></em></p>\r\n<p class=\"MsoNormal\" style=\"line-height: 106%; margin: 0cm 0cm 0cm 36.0pt;\"><strong style=\"mso-bidi-font-weight: normal;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">Shields Up: Hardware</span></em></strong></p>\r\n<p class=\"MsoNormal\" style=\"line-height: 106%; margin: 0cm 0cm 0cm 36.0pt;\"><strong style=\"mso-bidi-font-weight: normal;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\"><span style=\"mso-spacerun: yes;\">&nbsp;</span></span></em></strong></p>\r\n<ul style=\"margin-top: 0cm;\" type=\"circle\">\r\n<li class=\"MsoNormal\" style=\"color: black; margin-bottom: 0cm; line-height: 106%; mso-list: l1 level1 lfo1;\"><strong style=\"mso-bidi-font-weight: normal;\"><em style=\"mso-bidi-font-style: normal;\">Router</em></strong></li>\r\n</ul>\r\n<p style=\"padding-left: 40px;\"><em style=\"mso-bidi-font-style: normal;\">- Connects two or more networks together</em></p>\r\n<p style=\"padding-left: 40px;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">- Home router acts like firewall</span></em></p>\r\n<ul style=\"margin-top: 0cm;\" type=\"circle\">\r\n<li class=\"MsoNormal\" style=\"color: black; margin-bottom: 0cm; line-height: 106%; mso-list: l1 level1 lfo1;\"><strong style=\"mso-bidi-font-weight: normal;\"><em style=\"mso-bidi-font-style: normal;\">Network address translation (NAT) - </em></strong><em style=\"mso-bidi-font-style: normal;\">is a router security feature that shields devices on a private network (home) from the public network, Internet.</em></li>\r\n</ul>\r\n<ul style=\"margin-top: 0cm;\" type=\"circle\">\r\n<li class=\"MsoNormal\" style=\"color: black; margin-bottom: 0cm; line-height: 106%; mso-list: l1 level1 lfo1;\"><strong style=\"mso-bidi-font-weight: normal;\"><em style=\"mso-bidi-font-style: normal;\">Wireless router - </em></strong><em style=\"mso-bidi-font-style: normal;\">provides a wireless access point to your network. Use the router setup utility to change the SSID, service set identifier, or wireless network name, and enable and configure wireless encryption.</em></li>\r\n</ul>\r\n<p style=\"color: black; margin-bottom: 0cm; line-height: 106%;\"><em style=\"mso-bidi-font-style: normal;\"><img src=\"http://localhost/capstone/images/content/img_1763872824_d4983aa21e99af59.png\"></em></p>\r\n<p class=\"MsoNormal\" style=\"line-height: 106%; margin: 0cm 0cm 0cm 36.0pt;\"><strong style=\"mso-bidi-font-weight: normal;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">Figure 7. Wireless Network by wireless router</span></em></strong></p>\r\n<p class=\"MsoNormal\" style=\"line-height: 106%; margin: 0cm 0cm 0cm 36.0pt;\">&nbsp;</p>\r\n<p class=\"MsoNormal\" style=\"line-height: 106%; margin: 0cm 0cm 0cm 36.0pt;\"><strong style=\"mso-bidi-font-weight: normal;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">Shields Up: Operating System</span></em></strong></p>\r\n<p class=\"MsoNormal\" style=\"line-height: 106%; margin: 0cm 0cm 0cm 36.0pt;\"><strong style=\"mso-bidi-font-weight: normal;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\"><span style=\"mso-spacerun: yes;\">&nbsp;</span></span></em></strong></p>\r\n<ul style=\"margin-top: 0cm;\" type=\"circle\">\r\n<li class=\"MsoNormal\" style=\"color: black; margin-bottom: 0cm; line-height: 106%; mso-list: l1 level1 lfo1;\"><strong style=\"mso-bidi-font-weight: normal;\"><em style=\"mso-bidi-font-style: normal;\">Update OS</em></strong></li>\r\n</ul>\r\n<p class=\"MsoNormal\" style=\"text-indent: -18.0pt; line-height: 106%; mso-list: l0 level1 lfo2; margin: 0cm 0cm 0cm 137.6pt;\"><!-- [if !supportLists]--><span style=\"mso-ascii-font-family: Calibri; mso-fareast-font-family: Calibri; mso-hansi-font-family: Calibri; mso-bidi-font-family: Calibri; color: black;\"><span style=\"mso-list: Ignore;\">-<span style=\"font: 7.0pt \'Times New Roman\';\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span><!--[endif]--><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">Keep patched and up-to-date</span></em></p>\r\n<p class=\"MsoNormal\" style=\"line-height: 106%; margin: 0cm 0cm 0cm 36.0pt;\"><em style=\"mso-bidi-font-style: normal;\"><span style=\"color: black;\">The operating system is the most important piece of security software. It is best to keep it patched and up-to-date. By default, Windows and OS X computers are configured to automatically install updates. The only way to try to be safe is to be proactive and diligent in protecting your computer system.</span></em></p>', 6, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `quiz_options`
--

CREATE TABLE `quiz_options` (
  `id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `option_text` text NOT NULL,
  `is_correct` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quiz_questions`
--

CREATE TABLE `quiz_questions` (
  `id` int(11) NOT NULL,
  `quiz_id` int(11) DEFAULT NULL,
  `module_part_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `option1` varchar(255) NOT NULL,
  `option2` varchar(255) NOT NULL,
  `option3` varchar(255) NOT NULL,
  `option4` varchar(255) NOT NULL,
  `correct_answer` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quiz_results`
--

CREATE TABLE `quiz_results` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `score` int(11) NOT NULL,
  `percentage` decimal(5,2) DEFAULT 0.00,
  `completion_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quiz_results`
--

INSERT INTO `quiz_results` (`id`, `user_id`, `module_id`, `quiz_id`, `score`, `percentage`, `completion_date`) VALUES
(51, 31, 22, 13, 7, 28.00, '2025-11-24 13:30:52'),
(52, 32, 22, 13, 7, 28.00, '2025-11-24 14:25:52');

--
-- Triggers `quiz_results`
--
DELIMITER $$
CREATE TRIGGER `calculate_quiz_results_percentage_insert` BEFORE INSERT ON `quiz_results` FOR EACH ROW BEGIN
    DECLARE total_questions INT DEFAULT 0;
    
    SELECT COUNT(*) INTO total_questions
    FROM `final_quiz_questions`
    WHERE `quiz_id` = NEW.quiz_id;
    
    IF total_questions > 0 AND NEW.score >= 0 THEN
        SET NEW.percentage = LEAST(ROUND((NEW.score / total_questions) * 100, 2), 100.00);
    ELSE
        SET NEW.percentage = 0;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `calculate_quiz_results_percentage_update` BEFORE UPDATE ON `quiz_results` FOR EACH ROW BEGIN
    DECLARE total_questions INT DEFAULT 0;
    
    SELECT COUNT(*) INTO total_questions
    FROM `final_quiz_questions`
    WHERE `quiz_id` = NEW.quiz_id;
    
    IF total_questions > 0 AND NEW.score >= 0 THEN
        SET NEW.percentage = LEAST(ROUND((NEW.score / total_questions) * 100, 2), 100.00);
    ELSE
        SET NEW.percentage = 0;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `retake_results`
--

CREATE TABLE `retake_results` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `score` int(11) NOT NULL,
  `percentage` decimal(5,2) DEFAULT 0.00,
  `completion_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `section_quiz_questions`
--

CREATE TABLE `section_quiz_questions` (
  `id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `option1` varchar(255) NOT NULL,
  `option2` varchar(255) NOT NULL,
  `option3` varchar(255) NOT NULL,
  `option4` varchar(255) NOT NULL,
  `correct_answer` char(1) NOT NULL,
  `question_order` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `section` varchar(50) DEFAULT NULL,
  `role` enum('admin','student') NOT NULL,
  `profile_img` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `camera_agreement_accepted` tinyint(1) DEFAULT 0,
  `camera_agreement_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `password`, `gender`, `section`, `role`, `profile_img`, `created_at`, `updated_at`, `camera_agreement_accepted`, `camera_agreement_date`) VALUES
(1, 'Super', 'Admin', 'admin@admin.eyelearn', '$2y$10$5eql26ue0JmbvS6AAIQr/.pL8njF47sQ/.lDScg9/Gb..M.iZG1Ty', '', NULL, 'admin', 'default.png', '2025-04-21 15:01:17', '2025-04-21 16:07:51', 0, NULL),
(31, 'Mark Aljerick', 'De Castro', '0322-2068@lspu.edu.ph', '$2y$10$7O.GmiH3CE9/4Rb9qOKtcutk7FWSfyTOq9X03r5sOb24Q2ltz86qW', 'Male', 'BSINFO-1A', 'student', NULL, '2025-11-23 14:28:37', '2025-11-24 06:21:26', 1, '2025-11-24 14:21:26'),
(32, 'Vonn Annilov', 'Cabajes', '0322-2197@lspu.edu.ph', '$2y$10$pNlcZOVSctPbzmIudYe3geVGl1aK7CcYGBVnAcFkdsWHXmCus4td2', 'Female', 'BSINFO-1A', 'student', NULL, '2025-11-24 06:22:55', '2025-11-24 06:23:03', 1, '2025-11-24 14:23:03');

-- --------------------------------------------------------

--
-- Table structure for table `user_module_progress`
--

CREATE TABLE `user_module_progress` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `completed_sections` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`completed_sections`)),
  `last_accessed` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `completed_checkpoint_quizzes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '[]' CHECK (json_valid(`completed_checkpoint_quizzes`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_module_progress`
--

INSERT INTO `user_module_progress` (`id`, `user_id`, `module_id`, `completed_sections`, `last_accessed`, `completed_checkpoint_quizzes`) VALUES
(44, 31, 22, '[\"checkpoint_1\",\"77\",\"78\",\"79\",\"80\",\"81\",\"82\",\"83\",\"84\"]', '2025-11-24 05:29:25', '[]'),
(46, 32, 22, '[77,\"78\",\"79\",\"80\",\"81\",\"82\",\"83\",\"84\",\"checkpoint_1\"]', '2025-11-24 06:25:23', '[]');

-- --------------------------------------------------------

--
-- Table structure for table `user_progress`
--

CREATE TABLE `user_progress` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `module_id` int(11) DEFAULT NULL,
  `completion_percentage` decimal(5,2) DEFAULT 0.00,
  `last_accessed` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_progress`
--

INSERT INTO `user_progress` (`id`, `user_id`, `module_id`, `completion_percentage`, `last_accessed`) VALUES
(110, 32, 22, 13.00, '2025-11-24 06:24:37');

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `module_id` int(11) DEFAULT NULL,
  `session_start` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `session_end` timestamp NULL DEFAULT NULL,
  `total_duration_seconds` int(11) DEFAULT 0,
  `focused_duration_seconds` int(11) DEFAULT 0,
  `unfocused_duration_seconds` int(11) DEFAULT 0,
  `focus_percentage` decimal(5,2) DEFAULT 0.00,
  `session_type` enum('study','quiz','review') DEFAULT 'study',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_sessions`
--

INSERT INTO `user_sessions` (`id`, `user_id`, `module_id`, `session_start`, `session_end`, `total_duration_seconds`, `focused_duration_seconds`, `unfocused_duration_seconds`, `focus_percentage`, `session_type`, `created_at`) VALUES
(1, 4, 1, '2025-07-24 13:46:42', '2025-07-24 14:46:42', 3600, 2880, 720, 80.00, 'study', '2025-07-24 15:46:42'),
(2, 4, 2, '2025-07-23 12:46:42', '2025-07-23 13:46:42', 3600, 2700, 900, 75.00, 'study', '2025-07-24 15:46:42'),
(3, 4, 1, '2025-07-22 14:46:42', '2025-07-22 15:16:42', 1800, 1440, 360, 80.00, 'quiz', '2025-07-24 15:46:42'),
(4, 4, 3, '2025-07-21 13:46:42', '2025-07-21 14:46:42', 3600, 2520, 1080, 70.00, 'study', '2025-07-24 15:46:42'),
(5, 4, 2, '2025-07-20 14:46:42', '2025-07-20 15:16:42', 1800, 1350, 450, 75.00, 'review', '2025-07-24 15:46:42'),
(6, 4, 1, '2025-07-19 12:46:42', '2025-07-19 13:46:42', 3600, 2880, 720, 80.00, 'study', '2025-07-24 15:46:42'),
(7, 4, 3, '2025-07-18 13:46:42', '2025-07-18 14:46:42', 3600, 2520, 1080, 70.00, 'study', '2025-07-24 15:46:42'),
(8, 5, 1, '2025-07-24 12:46:42', '2025-07-24 13:46:42', 3600, 2700, 900, 75.00, 'study', '2025-07-24 15:46:42'),
(9, 5, 2, '2025-07-23 13:46:42', '2025-07-23 14:46:42', 3600, 3060, 540, 85.00, 'study', '2025-07-24 15:46:42'),
(10, 5, 1, '2025-07-22 14:46:42', '2025-07-22 15:16:42', 1800, 1530, 270, 85.00, 'quiz', '2025-07-24 15:46:42'),
(11, 5, 3, '2025-07-21 12:46:42', '2025-07-21 13:46:42', 3600, 2880, 720, 80.00, 'study', '2025-07-24 15:46:42'),
(12, 5, 2, '2025-07-20 13:46:42', '2025-07-20 14:46:42', 3600, 3060, 540, 85.00, 'study', '2025-07-24 15:46:42'),
(13, 5, 1, '2025-07-19 14:46:42', '2025-07-19 15:16:42', 1800, 1530, 270, 85.00, 'review', '2025-07-24 15:46:42'),
(14, 5, 3, '2025-07-18 12:46:42', '2025-07-18 13:46:42', 3600, 2880, 720, 80.00, 'study', '2025-07-24 15:46:42'),
(15, 6, 1, '2025-07-24 13:46:42', '2025-07-24 14:46:42', 3600, 2520, 1080, 70.00, 'study', '2025-07-24 15:46:42'),
(16, 6, 2, '2025-07-23 12:46:42', '2025-07-23 13:46:42', 3600, 2520, 1080, 70.00, 'study', '2025-07-24 15:46:42'),
(17, 6, 1, '2025-07-22 13:46:42', '2025-07-22 14:46:42', 3600, 2520, 1080, 70.00, 'quiz', '2025-07-24 15:46:42'),
(18, 6, 3, '2025-07-21 14:46:42', '2025-07-21 15:16:42', 1800, 1260, 540, 70.00, 'study', '2025-07-24 15:46:42'),
(19, 6, 2, '2025-07-20 12:46:42', '2025-07-20 13:46:42', 3600, 2520, 1080, 70.00, 'study', '2025-07-24 15:46:42'),
(20, 6, 1, '2025-07-19 13:46:42', '2025-07-19 14:46:42', 3600, 2520, 1080, 70.00, 'review', '2025-07-24 15:46:42'),
(21, 6, 3, '2025-07-18 14:46:42', '2025-07-18 15:16:42', 1800, 1260, 540, 70.00, 'study', '2025-07-24 15:46:42'),
(22, 7, 1, '2025-07-24 14:49:24', '2025-07-24 15:19:24', 1800, 1080, 720, 60.00, 'study', '2025-07-24 15:49:24'),
(23, 7, 2, '2025-07-23 13:49:24', '2025-07-23 14:49:24', 3600, 2160, 1440, 60.00, 'study', '2025-07-24 15:49:24'),
(24, 7, 1, '2025-07-22 14:49:24', '2025-07-22 15:19:24', 1800, 1080, 720, 60.00, 'quiz', '2025-07-24 15:49:24'),
(25, 7, 3, '2025-07-21 12:49:24', '2025-07-21 13:49:24', 3600, 2160, 1440, 60.00, 'study', '2025-07-24 15:49:24'),
(26, 8, 1, '2025-07-24 13:49:24', '2025-07-24 14:49:24', 3600, 3240, 360, 90.00, 'study', '2025-07-24 15:49:24'),
(27, 8, 2, '2025-07-23 12:49:24', '2025-07-23 13:49:24', 3600, 3240, 360, 90.00, 'study', '2025-07-24 15:49:24'),
(28, 8, 1, '2025-07-22 13:49:24', '2025-07-22 14:49:24', 3600, 3240, 360, 90.00, 'quiz', '2025-07-24 15:49:24'),
(29, 8, 3, '2025-07-21 14:49:24', '2025-07-21 15:19:24', 1800, 1620, 180, 90.00, 'study', '2025-07-24 15:49:24'),
(30, 8, 2, '2025-07-20 12:49:24', '2025-07-20 13:49:24', 3600, 3240, 360, 90.00, 'study', '2025-07-24 15:49:24'),
(31, 9, 1, '2025-07-24 14:49:24', '2025-07-24 15:04:24', 900, 360, 540, 40.00, 'study', '2025-07-24 15:49:24'),
(32, 9, 2, '2025-07-23 14:49:24', '2025-07-23 15:04:24', 900, 360, 540, 40.00, 'study', '2025-07-24 15:49:24'),
(33, 9, 1, '2025-07-21 14:49:24', '2025-07-21 15:04:24', 900, 360, 540, 40.00, 'study', '2025-07-24 15:49:24'),
(34, 10, 1, '2025-07-24 13:49:24', '2025-07-24 14:49:24', 3600, 1800, 1800, 50.00, 'study', '2025-07-24 15:49:24'),
(35, 10, 2, '2025-07-22 12:49:24', '2025-07-22 13:49:24', 3600, 2520, 1080, 70.00, 'study', '2025-07-24 15:49:24'),
(36, 10, 3, '2025-07-19 13:49:24', '2025-07-19 14:49:24', 3600, 2880, 720, 80.00, 'study', '2025-07-24 15:49:24');

-- --------------------------------------------------------

--
-- Table structure for table `user_study_sessions`
--

CREATE TABLE `user_study_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `focus_score` float NOT NULL,
  `duration` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ai_recommendations`
--
ALTER TABLE `ai_recommendations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_module` (`user_id`,`module_id`),
  ADD KEY `module_id` (`module_id`),
  ADD KEY `idx_user_module` (`user_id`,`module_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `assessments`
--
ALTER TABLE `assessments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `module_id` (`module_id`);

--
-- Indexes for table `checkpoint_quizzes`
--
ALTER TABLE `checkpoint_quizzes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_part_quiz` (`module_part_id`),
  ADD KEY `idx_module_part_id` (`module_part_id`);

--
-- Indexes for table `checkpoint_quiz_questions`
--
ALTER TABLE `checkpoint_quiz_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_checkpoint_quiz_id` (`checkpoint_quiz_id`);

--
-- Indexes for table `checkpoint_quiz_results`
--
ALTER TABLE `checkpoint_quiz_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_checkpoint_results_user` (`user_id`,`module_id`,`checkpoint_quiz_id`);

--
-- Indexes for table `daily_analytics`
--
ALTER TABLE `daily_analytics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_date` (`user_id`,`date`),
  ADD KEY `idx_date` (`date`);

--
-- Indexes for table `eye_tracking_analytics`
--
ALTER TABLE `eye_tracking_analytics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_module_date` (`user_id`,`module_id`,`section_id`,`date`),
  ADD KEY `idx_user_date` (`user_id`,`date`),
  ADD KEY `idx_module_date` (`module_id`,`date`);

--
-- Indexes for table `eye_tracking_data`
--
ALTER TABLE `eye_tracking_data`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `eye_tracking_sessions`
--
ALTER TABLE `eye_tracking_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_module` (`user_id`,`module_id`),
  ADD KEY `idx_user_section` (`user_id`,`section_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `final_quizzes`
--
ALTER TABLE `final_quizzes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `module_id` (`module_id`);

--
-- Indexes for table `final_quiz_questions`
--
ALTER TABLE `final_quiz_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quiz_id` (`quiz_id`);

--
-- Indexes for table `final_quiz_retakes`
--
ALTER TABLE `final_quiz_retakes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_module_quiz_pending` (`user_id`,`module_id`,`quiz_id`,`used`);

--
-- Indexes for table `focus_events`
--
ALTER TABLE `focus_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_session_id` (`session_id`),
  ADD KEY `idx_timestamp` (`timestamp`);

--
-- Indexes for table `modules`
--
ALTER TABLE `modules`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `module_completions`
--
ALTER TABLE `module_completions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_completion` (`user_id`,`module_id`);

--
-- Indexes for table `module_parts`
--
ALTER TABLE `module_parts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `module_id` (`module_id`);

--
-- Indexes for table `module_sections`
--
ALTER TABLE `module_sections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `module_part_id` (`module_part_id`);

--
-- Indexes for table `quiz_options`
--
ALTER TABLE `quiz_options`
  ADD PRIMARY KEY (`id`),
  ADD KEY `question_id` (`question_id`);

--
-- Indexes for table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `module_part_id` (`module_part_id`),
  ADD KEY `quiz_id` (`quiz_id`);

--
-- Indexes for table `quiz_results`
--
ALTER TABLE `quiz_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `module_id` (`module_id`);

--
-- Indexes for table `retake_results`
--
ALTER TABLE `retake_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_retake_results_user` (`user_id`,`module_id`,`quiz_id`);

--
-- Indexes for table `section_quiz_questions`
--
ALTER TABLE `section_quiz_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `section_id` (`section_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_module_progress`
--
ALTER TABLE `user_module_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_module` (`user_id`,`module_id`);

--
-- Indexes for table `user_progress`
--
ALTER TABLE `user_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_module_unique` (`user_id`,`module_id`),
  ADD KEY `module_id` (`module_id`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_session_start` (`session_start`),
  ADD KEY `idx_user_date` (`user_id`,`session_start`);

--
-- Indexes for table `user_study_sessions`
--
ALTER TABLE `user_study_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ai_recommendations`
--
ALTER TABLE `ai_recommendations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=121;

--
-- AUTO_INCREMENT for table `assessments`
--
ALTER TABLE `assessments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `checkpoint_quizzes`
--
ALTER TABLE `checkpoint_quizzes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `checkpoint_quiz_questions`
--
ALTER TABLE `checkpoint_quiz_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `checkpoint_quiz_results`
--
ALTER TABLE `checkpoint_quiz_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `daily_analytics`
--
ALTER TABLE `daily_analytics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `eye_tracking_analytics`
--
ALTER TABLE `eye_tracking_analytics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=297;

--
-- AUTO_INCREMENT for table `eye_tracking_data`
--
ALTER TABLE `eye_tracking_data`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `eye_tracking_sessions`
--
ALTER TABLE `eye_tracking_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=311;

--
-- AUTO_INCREMENT for table `final_quizzes`
--
ALTER TABLE `final_quizzes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `final_quiz_questions`
--
ALTER TABLE `final_quiz_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `final_quiz_retakes`
--
ALTER TABLE `final_quiz_retakes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `focus_events`
--
ALTER TABLE `focus_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `modules`
--
ALTER TABLE `modules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `module_completions`
--
ALTER TABLE `module_completions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=133;

--
-- AUTO_INCREMENT for table `module_parts`
--
ALTER TABLE `module_parts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `module_sections`
--
ALTER TABLE `module_sections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=85;

--
-- AUTO_INCREMENT for table `quiz_options`
--
ALTER TABLE `quiz_options`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `quiz_results`
--
ALTER TABLE `quiz_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `retake_results`
--
ALTER TABLE `retake_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

--
-- AUTO_INCREMENT for table `section_quiz_questions`
--
ALTER TABLE `section_quiz_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `user_module_progress`
--
ALTER TABLE `user_module_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `user_progress`
--
ALTER TABLE `user_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=111;

--
-- AUTO_INCREMENT for table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `user_study_sessions`
--
ALTER TABLE `user_study_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `ai_recommendations`
--
ALTER TABLE `ai_recommendations`
  ADD CONSTRAINT `ai_recommendations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ai_recommendations_ibfk_2` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `assessments`
--
ALTER TABLE `assessments`
  ADD CONSTRAINT `assessments_ibfk_1` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `checkpoint_quizzes`
--
ALTER TABLE `checkpoint_quizzes`
  ADD CONSTRAINT `checkpoint_quizzes_ibfk_1` FOREIGN KEY (`module_part_id`) REFERENCES `module_parts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `checkpoint_quiz_questions`
--
ALTER TABLE `checkpoint_quiz_questions`
  ADD CONSTRAINT `checkpoint_quiz_questions_ibfk_1` FOREIGN KEY (`checkpoint_quiz_id`) REFERENCES `checkpoint_quizzes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `eye_tracking_data`
--
ALTER TABLE `eye_tracking_data`
  ADD CONSTRAINT `eye_tracking_data_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `final_quizzes`
--
ALTER TABLE `final_quizzes`
  ADD CONSTRAINT `final_quizzes_ibfk_1` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`);

--
-- Constraints for table `final_quiz_questions`
--
ALTER TABLE `final_quiz_questions`
  ADD CONSTRAINT `final_quiz_questions_ibfk_1` FOREIGN KEY (`quiz_id`) REFERENCES `final_quizzes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `module_parts`
--
ALTER TABLE `module_parts`
  ADD CONSTRAINT `module_parts_ibfk_1` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`);

--
-- Constraints for table `module_sections`
--
ALTER TABLE `module_sections`
  ADD CONSTRAINT `module_sections_ibfk_1` FOREIGN KEY (`module_part_id`) REFERENCES `module_parts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `quiz_options`
--
ALTER TABLE `quiz_options`
  ADD CONSTRAINT `quiz_options_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `quiz_questions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  ADD CONSTRAINT `quiz_questions_ibfk_1` FOREIGN KEY (`module_part_id`) REFERENCES `module_parts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `quiz_questions_ibfk_2` FOREIGN KEY (`quiz_id`) REFERENCES `final_quizzes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `quiz_results`
--
ALTER TABLE `quiz_results`
  ADD CONSTRAINT `quiz_results_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `quiz_results_ibfk_2` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`);

--
-- Constraints for table `section_quiz_questions`
--
ALTER TABLE `section_quiz_questions`
  ADD CONSTRAINT `section_quiz_questions_ibfk_1` FOREIGN KEY (`section_id`) REFERENCES `module_sections` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_progress`
--
ALTER TABLE `user_progress`
  ADD CONSTRAINT `user_progress_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `user_progress_ibfk_2` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`);

--
-- Constraints for table `user_study_sessions`
--
ALTER TABLE `user_study_sessions`
  ADD CONSTRAINT `user_study_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
