-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 29, 2025 at 03:36 AM
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
-- Database: `banthi_anctodorisk`
--

-- --------------------------------------------------------

--
-- Table structure for table `anc_risk`
--

CREATE TABLE `anc_risk` (
  `risk_id` int(11) NOT NULL,
  `risk_name` text CHARACTER SET tis620 COLLATE tis620_thai_ci NOT NULL,
  `risk_status` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `anc_risk`
--

INSERT INTO `anc_risk` (`risk_id`, `risk_name`, `risk_status`) VALUES
(1, 'อายุ >=15 ถึง 35 ปี (อายุนับถึง EDC)', 'GeneralRisks'),
(2, 'มารดาสูง <=145 ซม.', 'GeneralRisks'),
(3, 'BMI ก่อนตั้งครรภ์ 27-30', 'GeneralRisks'),
(4, 'อายุ < 15ปี, >35 ปี', 'GeneralRisks'),
(5, 'BMI <18.5, BMI > 30', 'GeneralRisks'),
(6, 'แม่มีความพิการ ที่มีผลต่อการคลอด การเบ่งคลอด มีปัญหาเกี่ยวกับกระดูกอุ้งเชิงกราน แขนขาด ปัญหาการสื่อสาร', 'GeneralRisks'),
(7, 'ติดเหล้า', 'GeneralRisks'),
(8, 'ติดบุหรี่', 'GeneralRisks'),
(9, 'ติดยาเสพติด', 'GeneralRisks'),
(10, 'ประเมินสุขภาพจิตโดยใช้ 9Q คะแนน >= 7 ขึ้นไป', 'GeneralRisks'),
(11, 'โรคทางจิตเวช', 'GeneralRisks'),
(12, 'Pregnancy with Rh negative', 'GeneralRisks'),
(13, 'Uncertain date', 'GeneralRisks'),
(14, 'Late ANC', 'GeneralRisks'),
(15, '', 'GeneralRisks'),
(16, 'กลุ่มเสี่ยงต่อ GDM', 'MedicalRisks'),
(17, 'Pregnancy With DM ที่ควบคุมอาการได้', 'MedicalRisks'),
(18, 'Pregnancy With DM ที่ควบคุมไม่ได้ และมีภาวะแทรกซ้อน', 'MedicalRisks'),
(19, 'Pregnancy With HT ที่ควบคุมอาการได้', 'MedicalRisks'),
(20, 'Pregnancy With HT ที่ควบคุมไม่ได้ และมีภาวะแทรกซ้อน', 'MedicalRisks'),
(21, 'Pregnancy With Epilepsy ที่มีประวัติชัก', 'MedicalRisks'),
(22, 'Pregnancy With heart disease, functional class I,II', 'MedicalRisks'),
(23, 'Pregnancy With heart disease, functional class III,IV', 'MedicalRisks'),
(24, 'Pregnancy With iron deficiency anemia (Hct 28-32%), thalassemia trait', 'MedicalRisks'),
(25, 'Pregnancy With thalassemia disease', 'MedicalRisks'),
(26, 'Pregnancy With Thyroid ที่ควบคุมอาการได้', 'MedicalRisks'),
(27, 'Pregnancy With Med อื่นๆ ที่ควบคุมอาการได้', 'MedicalRisks'),
(28, 'Pregnancy With SLE', 'MedicalRisks'),
(29, 'Pregnancy With Hyperthyroid, Hypothyroid', 'MedicalRisks'),
(30, 'Pregnancy with HIV (ที่ไม่มีภาวะแทรกซ้อน)', 'MedicalRisks'),
(31, 'Pregnancy with HIV (ที่มีภาวะแทรกซ้อน TB,PCP)', 'MedicalRisks'),
(32, 'Pregnancy with VDRL,STD', 'MedicalRisks'),
(33, 'Previous c/s', 'obstetricRisks'),
(34, 'Twin Pregnancy', 'obstetricRisks'),
(35, 'Triplet Pregnancy', 'obstetricRisks'),
(36, 'ผ่านการคลอด 4 ครั้งขึ้นไป', 'obstetricRisks'),
(37, 'เคยคลอดทารกน้ำหนัก > 4000 กรัม', 'obstetricRisks'),
(38, 'GA > 40 สัปดาห์', 'obstetricRisks'),
(39, 'มีประวัติเสี่ยงทางสูติกรรม เช่น ได้รับยายับยั้งการคลอด , คลอดก่อนกำหนด , ตกเลือด , คลอดติดไหล่ , ทารกเสียชีวิตในครรภ์ , น้ำหนักทารก < 2500 กรัม', 'obstetricRisks'),
(40, 'Pregnancy with condyloma', 'obstetricRisks'),
(41, 'Placenta previa totalis', 'obstetricRisks'),
(42, 'Pregnancy with myoma, ovarian tumor', 'obstetricRisks'),
(43, 'Pregnancy with PIH without severe feature', 'obstetricRisks'),
(44, 'PIH with severe feature', 'obstetricRisks'),
(45, 'pregnancy with fetal anomaly', 'obstetricRisks'),
(46, 'เลือกออกในไตรมาส 1', 'obstetricRisks'),
(47, 'เลือกออกในไตรมาส 2,3', 'obstetricRisks'),
(48, 'Oligohydamios AFI < 5 cm', 'obstetricRisks'),
(49, 'Polyhydamios > 2cm', 'obstetricRisks');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `anc_risk`
--
ALTER TABLE `anc_risk`
  ADD PRIMARY KEY (`risk_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `anc_risk`
--
ALTER TABLE `anc_risk`
  MODIFY `risk_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
