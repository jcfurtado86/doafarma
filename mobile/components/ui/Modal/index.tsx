import { ReactNode } from 'react';
import { Modal as RNModal, Pressable, ScrollView, Text, View } from 'react-native';

import { a11y } from '@/utils/accessibility';

import { styles, type ModalAnimation } from './styles';

export type { ModalAnimation };

export interface ModalProps {
  visible: boolean;
  onClose: () => void;
  title: string;
  children: ReactNode;
  footer?: ReactNode;
  dismissOnBackdrop?: boolean;
  animationType?: ModalAnimation;
  testID?: string;
}

const NOOP = () => {};

export function Modal({
  visible,
  onClose,
  title,
  children,
  footer,
  dismissOnBackdrop = true,
  animationType = 'slide',
  testID,
}: ModalProps) {
  const overlayStyle = [
    styles.overlayBase,
    animationType === 'slide' ? styles.overlaySlide : styles.overlayFade,
  ];
  const containerStyle = [
    styles.containerBase,
    animationType === 'slide' ? styles.containerSlide : styles.containerFade,
  ];

  const handleBackdropPress = dismissOnBackdrop ? onClose : undefined;

  return (
    <RNModal
      visible={visible}
      animationType={animationType}
      transparent
      onRequestClose={onClose}
      testID={testID}
    >
      <Pressable
        style={overlayStyle}
        onPress={handleBackdropPress}
        testID={testID ? `${testID}-backdrop` : undefined}
      >
        <Pressable
          style={containerStyle}
          onPress={NOOP}
          testID={testID ? `${testID}-container` : undefined}
        >
          <View style={styles.header}>
            <Text style={styles.title} numberOfLines={1}>
              {title}
            </Text>
            <Pressable
              {...a11y.closeButton()}
              onPress={onClose}
              style={styles.closeButton}
              testID={testID ? `${testID}-close` : undefined}
            >
              <Text style={styles.closeButtonText}>✕</Text>
            </Pressable>
          </View>
          <ScrollView
            style={styles.body}
            contentContainerStyle={styles.bodyContent}
            showsVerticalScrollIndicator={false}
          >
            {children}
          </ScrollView>
          {footer && <View style={styles.footer}>{footer}</View>}
        </Pressable>
      </Pressable>
    </RNModal>
  );
}
