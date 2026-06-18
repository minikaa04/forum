-- Ekşi Sözlük Style Forum Database
-- Run this in phpMyAdmin or MySQL CLI

CREATE DATABASE IF NOT EXISTS forum_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE forum_db;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    display_name VARCHAR(100) DEFAULT NULL,
    bio TEXT DEFAULT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    role ENUM('user','admin') DEFAULT 'user',
    is_blocked TINYINT(1) DEFAULT 0,
    last_seen DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Topics table
CREATE TABLE topics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    user_id INT NOT NULL,
    views INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Comments table
CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    topic_id INT NOT NULL,
    user_id INT NOT NULL,
    parent_id INT DEFAULT NULL,
    content TEXT NOT NULL,
    is_hidden TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (topic_id) REFERENCES topics(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Comment votes (like/dislike)
CREATE TABLE comment_votes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    comment_id INT NOT NULL,
    user_id INT NOT NULL,
    vote TINYINT NOT NULL, -- 1 = like, -1 = dislike
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_vote (comment_id, user_id),
    FOREIGN KEY (comment_id) REFERENCES comments(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Subscriptions
CREATE TABLE subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    topic_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_sub (user_id, topic_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (topic_id) REFERENCES topics(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Friend requests
CREATE TABLE friend_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    status ENUM('pending','accepted','rejected') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_friend (sender_id, receiver_id),
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Message requests (must be approved before chatting)
CREATE TABLE message_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    status ENUM('pending','accepted','rejected') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_msg_req (sender_id, receiver_id),
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Messages
CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    content TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Notifications
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('comment_reply','friend_request','message_request','system') NOT NULL,
    content TEXT NOT NULL,
    link VARCHAR(255) DEFAULT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================
-- SEED DATA
-- =====================

-- Admin account (password: admin123)
INSERT INTO users (username, email, password, display_name, bio, role, last_seen) VALUES
('admin', 'admin@forum.com', '$2y$10$P8mvxZT.KHBDwvWVfpIcEe/4KXMItxwvp2KhcWkll0wvToHcJ16JC', 'Yönetici', 'Forum yöneticisi. Kurallara uyulmasını sağlarım.', 'admin', NOW());

-- Fake users (password: 123456 for all)
INSERT INTO users (username, email, password, display_name, bio, last_seen) VALUES
('ahmet', 'ahmet@forum.com', '$2y$10$8eOQ5ynCUDjK/Ry2Khxn8ePaYLr0WskiMQe7yKznFdvkVcJAluMGe', 'Ahmet Yılmaz', 'İstanbul''da yaşıyorum. Teknoloji ve felsefe ile ilgileniyorum.', DATE_SUB(NOW(), INTERVAL 2 MINUTE)),
('elif', 'elif@forum.com', '$2y$10$8eOQ5ynCUDjK/Ry2Khxn8ePaYLr0WskiMQe7yKznFdvkVcJAluMGe', 'Elif Demir', 'Edebiyat öğretmeni. Kitaplar hakkında konuşmayı severim.', DATE_SUB(NOW(), INTERVAL 30 MINUTE)),
('mehmet', 'mehmet@forum.com', '$2y$10$8eOQ5ynCUDjK/Ry2Khxn8ePaYLr0WskiMQe7yKznFdvkVcJAluMGe', 'Mehmet Kaya', 'Ankara''dan bir yazılımcı. Açık kaynak projelere katkıda bulunuyorum.', DATE_SUB(NOW(), INTERVAL 3 HOUR)),
('zeynep', 'zeynep@forum.com', '$2y$10$8eOQ5ynCUDjK/Ry2Khxn8ePaYLr0WskiMQe7yKznFdvkVcJAluMGe', 'Zeynep Arslan', 'Müzik ve sinema tutkunu. Her türlü tartışmaya açığım.', DATE_SUB(NOW(), INTERVAL 1 DAY)),
('burak', 'burak@forum.com', '$2y$10$8eOQ5ynCUDjK/Ry2Khxn8ePaYLr0WskiMQe7yKznFdvkVcJAluMGe', 'Burak Öztürk', 'İzmir''de üniversite öğrencisi. Spor ve teknoloji hakkında yazarım.', DATE_SUB(NOW(), INTERVAL 2 DAY));

-- Sample Topics
INSERT INTO topics (title, description, user_id, views, created_at) VALUES
('Yapay zeka insanlığın sonu mu yoksa başlangıcı mı?', 'Yapay zekanın gelişimi hakkında ne düşünüyorsunuz? İnsanlık için bir tehdit mi yoksa yeni bir çağın başlangıcı mı?', 2, 142, DATE_SUB(NOW(), INTERVAL 5 DAY)),
('En iyi Türk filmi hangisidir?', 'Türk sinemasının en iyi filmi hakkında herkesin farklı bir görüşü var. Sizce hangisi?', 4, 89, DATE_SUB(NOW(), INTERVAL 4 DAY)),
('Uzaktan çalışma kültürü kalıcı mı?', 'Pandemi sonrası uzaktan çalışma alışkanlığımız değişti. Bu kalıcı bir değişim mi?', 5, 67, DATE_SUB(NOW(), INTERVAL 3 DAY)),
('Kahve mi çay mı?', 'Türkiye''nin en büyük tartışması. Siz hangi taraftasınız?', 3, 234, DATE_SUB(NOW(), INTERVAL 2 DAY)),
('İstanbul mu Ankara mı?', 'Yaşamak için hangi şehir daha iyi? Avantajları ve dezavantajlarıyla tartışalım.', 2, 178, DATE_SUB(NOW(), INTERVAL 1 DAY)),
('Kitap okuma alışkanlığı nasıl kazanılır?', 'Kitap okumak istiyorum ama bir türlü alışkanlık haline getiremiyorum. Önerileriniz nelerdir?', 3, 56, DATE_SUB(NOW(), INTERVAL 12 HOUR)),
('Kripto paralar güvenilir mi?', 'Kripto paralara yatırım yapmak mantıklı mı? Deneyimlerinizi paylaşır mısınız?', 6, 91, DATE_SUB(NOW(), INTERVAL 6 HOUR)),
('Yurt dışında yaşamanın artıları ve eksileri', 'Yurt dışında yaşayan veya yaşamayı düşünenler için bir tartışma başlığı.', 5, 123, DATE_SUB(NOW(), INTERVAL 3 HOUR));

-- Sample Comments
INSERT INTO comments (topic_id, user_id, parent_id, content, created_at) VALUES
-- Topic 1: AI
(1, 3, NULL, 'Yapay zeka kesinlikle insanlığın en büyük buluşlarından biri. Ama kontrol mekanizmaları şart.', DATE_SUB(NOW(), INTERVAL 4 DAY)),
(1, 4, NULL, 'Ben biraz endişeliyim açıkçası. İş gücü üzerindeki etkisi çok büyük olacak.', DATE_SUB(NOW(), INTERVAL 4 DAY)),
(1, 5, 1, 'Katılıyorum, düzenleme olmadan tehlikeli olabilir. Ama tamamen durdurmak da çözüm değil.', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(1, 6, 2, 'İş gücü dönüşecek ama yok olmayacak. Yeni meslekler ortaya çıkacak.', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(1, 2, NULL, 'Yapay zeka bir araçtır. Onu nasıl kullanacağımız bize bağlı.', DATE_SUB(NOW(), INTERVAL 2 DAY)),

-- Topic 2: Best Turkish Film
(2, 2, NULL, 'Kesinlikle "Babam ve Oğlum". Her izlediğimde ağlarım.', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(2, 5, NULL, 'Bence "Eşkıya" Türk sinemasının zirvesidir.', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(2, 6, 6, 'Eşkıya gerçekten muhteşem bir film ama ben "Nefes" derdim.', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 3, NULL, '"Kış Uykusu" Cannes''da Altın Palmiye aldı, o yüzden o bence.', DATE_SUB(NOW(), INTERVAL 2 DAY)),

-- Topic 3: Remote Work
(3, 2, NULL, 'Uzaktan çalışma verimimi artırdı. Ofise dönmek istemiyorum.', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(3, 3, 10, 'Bende aynı şekilde ama sosyal etkileşim eksikliği var.', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(3, 4, NULL, 'Hibrit model en iyisi bence. Hem ev hem ofis.', DATE_SUB(NOW(), INTERVAL 1 DAY)),

-- Topic 4: Coffee vs Tea
(4, 2, NULL, 'Çay tabii ki! Türk çayı olmadan sabah başlamaz.', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(4, 5, NULL, 'Kahve. Özellikle Türk kahvesi. Tartışmaya bile gerek yok.', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(4, 6, 13, 'Çay her zaman bir adım önde. Misafirlikte çay ikram edilir, kahve değil.', DATE_SUB(NOW(), INTERVAL 20 HOUR)),
(4, 4, 14, 'Kahvaltıda çay, öğleden sonra kahve. İkisi de güzel.', DATE_SUB(NOW(), INTERVAL 18 HOUR)),
(4, 3, NULL, 'Bu konuda uzlaşmak imkansız. İkisi de kendi yerinde mükemmel.', DATE_SUB(NOW(), INTERVAL 12 HOUR)),

-- Topic 5: Istanbul vs Ankara
(5, 3, NULL, 'İstanbul kültürel açıdan çok zengin ama trafik çekilmez.', DATE_SUB(NOW(), INTERVAL 20 HOUR)),
(5, 4, 18, 'Kesinlikle! Ankara''da ulaşım çok daha rahat.', DATE_SUB(NOW(), INTERVAL 18 HOUR)),
(5, 6, NULL, 'İstanbul''un enerjisi bambaşka. Ankara biraz sakin kalıyor.', DATE_SUB(NOW(), INTERVAL 15 HOUR)),

-- Topic 6: Reading Habit
(6, 2, NULL, 'Her gün 20 dakika ile başlayın. Zamanla alışkanlık haline gelir.', DATE_SUB(NOW(), INTERVAL 10 HOUR)),
(6, 4, 21, 'Kesinlikle. Küçük hedeflerle başlamak çok önemli.', DATE_SUB(NOW(), INTERVAL 8 HOUR)),

-- Topic 7: Crypto
(7, 2, NULL, 'Kripto çok volatil. Kaybetmeyi göze alabileceğiniz kadar yatırın.', DATE_SUB(NOW(), INTERVAL 5 HOUR)),
(7, 3, 23, 'Aynen, asla tüm birikimini koymayın.', DATE_SUB(NOW(), INTERVAL 4 HOUR)),
(7, 4, NULL, 'Ben Bitcoin ile güzel kazandım ama altcoinlerden uzak duruyorum.', DATE_SUB(NOW(), INTERVAL 3 HOUR));

-- Sample Votes
INSERT INTO comment_votes (comment_id, user_id, vote) VALUES
(1, 2, 1), (1, 4, 1), (1, 5, 1),
(2, 3, 1), (2, 5, -1),
(3, 2, 1), (3, 6, 1),
(5, 3, 1), (5, 4, 1), (5, 6, 1),
(6, 3, 1), (6, 5, 1),
(7, 2, 1), (7, 4, 1),
(10, 3, 1), (10, 4, 1),
(13, 3, 1), (13, 5, 1), (13, 6, 1),
(14, 2, 1), (14, 6, -1);

-- Friend relationships
INSERT INTO friend_requests (sender_id, receiver_id, status) VALUES
(2, 3, 'accepted'),
(2, 4, 'accepted'),
(3, 5, 'accepted'),
(4, 6, 'accepted'),
(5, 2, 'pending'),
(6, 3, 'pending');

-- Message requests
INSERT INTO message_requests (sender_id, receiver_id, status) VALUES
(2, 3, 'accepted'),
(4, 5, 'accepted'),
(6, 2, 'pending');

-- Sample messages
INSERT INTO messages (sender_id, receiver_id, content, is_read, created_at) VALUES
(2, 3, 'Selam Elif! Edebiyat hakkında konuşmak isterim.', 1, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(3, 2, 'Tabii ki Ahmet, hangi tür kitapları seviyorsun?', 1, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 3, 'Genelde bilim kurgu okuyorum. Sen?', 1, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(3, 2, 'Ben daha çok klasik edebiyat tercih ediyorum. Dostoyevski favorim.', 0, DATE_SUB(NOW(), INTERVAL 12 HOUR)),
(4, 5, 'Burak, spor hakkında bir başlık açalım mı?', 1, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(5, 4, 'Olur! Futbol mu yoksa basketbol mu?', 0, DATE_SUB(NOW(), INTERVAL 6 HOUR));

-- Sample subscriptions
INSERT INTO subscriptions (user_id, topic_id) VALUES
(2, 1), (2, 4), (2, 5),
(3, 1), (3, 2), (3, 4),
(4, 2), (4, 3),
(5, 1), (5, 3), (5, 7),
(6, 4), (6, 5), (6, 7);

-- Sample notifications
INSERT INTO notifications (user_id, type, content, link, is_read, created_at) VALUES
(2, 'comment_reply', 'Mehmet yorumunuza yanıt verdi', 'topic.php?id=1', 0, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(2, 'friend_request', 'Burak size arkadaşlık isteği gönderdi', 'notifications.php', 0, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(3, 'message_request', 'Burak size mesaj isteği gönderdi', 'notifications.php', 0, DATE_SUB(NOW(), INTERVAL 12 HOUR)),
(2, 'system', 'Foruma hoş geldiniz! Profilinizi tamamlamayı unutmayın.', 'edit_profile.php', 1, DATE_SUB(NOW(), INTERVAL 5 DAY)),
(3, 'comment_reply', 'Zeynep yorumunuza yanıt verdi', 'topic.php?id=4', 0, DATE_SUB(NOW(), INTERVAL 18 HOUR));
