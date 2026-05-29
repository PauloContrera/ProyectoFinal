import * as React from "react";
import { motion } from "framer-motion";
import { MenuToggle } from "./MenuToggle";

interface MenuDesplejableProps {
  isOpen: boolean;
  toggleMenu: () => void;
}

export const MenuDesplejable: React.FC<MenuDesplejableProps> = ({
  isOpen,
  toggleMenu,
}) => {
  return (
    <motion.nav
      initial={false}
      animate={isOpen ? "open" : "closed"}
    >
      <MenuToggle toggle={toggleMenu} />
    </motion.nav>
  );
};
