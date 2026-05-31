-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 31/05/2026 às 19:28
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
(1, 'Acidente de trânsito com vítima', 'UR-10107', 'confirmado_base', '2026-05-31 19:02:33.405', '2026-05-31 19:02:36.818'),
(2, 'Teste: ESP32 fora de serviço.', 'UR-10103', 'falha_timeout', '2026-05-31 19:03:26.713', NULL),
(3, 'Incêndio em vegetação', 'UR-10107', 'confirmado_base', '2026-05-31 19:07:55.113', '2026-05-31 19:08:14.427');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
