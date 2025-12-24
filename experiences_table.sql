CREATE TABLE experiences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE,
    startingTime TIME,
    endingTime TIME,
    kilometers FLOAT,
    weather VARCHAR(20),
    visibility VARCHAR(20),
    traffic VARCHAR(20),
    roadCondition VARCHAR(20)
);

CREATE TABLE maneuvers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL
);

INSERT INTO maneuvers (name) VALUES
('Parallel parking'),
('Lane change'),
('Overtaking'),
('Roundabout'),
('U-turning');


CREATE TABLE experience_maneuver (
    experience_id INT NOT NULL,
    maneuver_id INT NOT NULL,

    PRIMARY KEY (experience_id, maneuver_id),

    FOREIGN KEY (experience_id)
        REFERENCES experiences(id)
        ON DELETE CASCADE,

    FOREIGN KEY (maneuver_id)
        REFERENCES maneuvers(id)
        ON DELETE CASCADE
);
