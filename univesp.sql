-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 20/05/2026 às 00:15
-- Versão do servidor: 10.5.19-MariaDB-0+deb11u2
-- Versão do PHP: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `univesp`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `ocorrencias`
--

CREATE TABLE `ocorrencias` (
  `id` int(11) NOT NULL,
  `descricao` varchar(255) NOT NULL,
  `viatura` varchar(50) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Pendente',
  `data_registro` datetime(3) DEFAULT current_timestamp(3),
  `data_confirmacao_base` datetime(3) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `ocorrencias`
--

INSERT INTO `ocorrencias` (`id`, `descricao`, `viatura`, `status`, `data_registro`, `data_confirmacao_base`) VALUES
(1, 'Acidente de Trânsito com Vítima - Av. Principal', 'UR-10101', 'confirmado_base', '2026-05-19 21:36:49.296', '2026-05-19 23:09:13.763'),
(2, 'Acidente 1', 'UR-10103', 'confirmado_base', '2026-05-19 22:00:22.565', '2026-05-19 22:02:02.629'),
(3, 'sde', 'UR-10101', 'confirmado_base', '2026-05-19 22:02:48.641', '2026-05-19 23:04:11.707'),
(4, 'teste', 'UR-10101', 'confirmado_base', '2026-05-19 22:10:15.160', '2026-05-19 23:12:23.662'),
(5, '107', 'UR-10107', 'confirmado_base', '2026-05-19 22:10:34.382', '2026-05-19 22:10:44.303'),
(6, '103', 'UR-10103', 'confirmado_base', '2026-05-19 22:19:09.307', '2026-05-19 23:24:18.625'),
(7, '7', 'UR-10107', 'confirmado_base', '2026-05-19 22:22:26.216', '2026-05-19 23:31:48.961'),
(8, '8', 'UR-10101', 'confirmado_base', '2026-05-19 22:25:43.688', '2026-05-19 23:31:55.095'),
(9, '7787', 'UR-10101', 'acionado', '2026-05-19 23:41:46.566', NULL),
(10, 'ESP32-S3-WROOM', 'UR-10107', 'confirmado_base', '2026-05-19 23:42:01.606', '2026-05-19 23:42:04.566');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `ocorrencias`
--
ALTER TABLE `ocorrencias`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `ocorrencias`
--
ALTER TABLE `ocorrencias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
