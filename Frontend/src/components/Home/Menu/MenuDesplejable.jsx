import * as React from "react";
import { motion } from "framer-motion";
import { MenuToggle } from "./MenuToggle";

export const MenuDesplejable = ({ isOpen, toggleMenu }) => {
  return (
    <motion.nav
      initial={false}
      animate={isOpen ? "open" : "closed"}
    >
      <MenuToggle toggle={toggleMenu} />
    </motion.nav>
  );
};
