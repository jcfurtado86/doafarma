import React from 'react';
import { Modal as RNModal, StyleSheet, Text } from 'react-native';

import { fireEvent, render } from '@testing-library/react-native';

import { Modal } from '.';

const TEST_ID = 'ui-modal';

describe('Modal', () => {
  it('renders title, children and close button when visible', () => {
    const { getByText, getByTestId } = render(
      <Modal visible title="Contrapor" onClose={jest.fn()} testID={TEST_ID}>
        <Text>Body content</Text>
      </Modal>
    );

    expect(getByText('Contrapor')).toBeTruthy();
    expect(getByText('Body content')).toBeTruthy();
    expect(getByTestId(`${TEST_ID}-close`)).toBeTruthy();
  });

  it('calls onClose when the close button is pressed', () => {
    const onClose = jest.fn();
    const { getByTestId } = render(
      <Modal visible title="x" onClose={onClose} testID={TEST_ID}>
        <Text>body</Text>
      </Modal>
    );

    fireEvent.press(getByTestId(`${TEST_ID}-close`));
    expect(onClose).toHaveBeenCalledTimes(1);
  });

  it('calls onClose when the backdrop is pressed by default', () => {
    const onClose = jest.fn();
    const { getByTestId } = render(
      <Modal visible title="x" onClose={onClose} testID={TEST_ID}>
        <Text>body</Text>
      </Modal>
    );

    fireEvent.press(getByTestId(`${TEST_ID}-backdrop`));
    expect(onClose).toHaveBeenCalledTimes(1);
  });

  it('does not call onClose when backdrop is pressed with dismissOnBackdrop={false}', () => {
    const onClose = jest.fn();
    const { getByTestId } = render(
      <Modal visible title="x" onClose={onClose} dismissOnBackdrop={false} testID={TEST_ID}>
        <Text>body</Text>
      </Modal>
    );

    fireEvent.press(getByTestId(`${TEST_ID}-backdrop`));
    expect(onClose).not.toHaveBeenCalled();
  });

  it('does not call onClose when pressing the container (touch absorption)', () => {
    const onClose = jest.fn();
    const { getByTestId } = render(
      <Modal visible title="x" onClose={onClose} testID={TEST_ID}>
        <Text>body</Text>
      </Modal>
    );

    fireEvent.press(getByTestId(`${TEST_ID}-container`));
    expect(onClose).not.toHaveBeenCalled();
  });

  it('renders footer when provided', () => {
    const { getByTestId } = render(
      <Modal
        visible
        title="x"
        onClose={jest.fn()}
        testID={TEST_ID}
        footer={<Text testID="modal-footer">actions</Text>}
      >
        <Text>body</Text>
      </Modal>
    );

    expect(getByTestId('modal-footer')).toBeTruthy();
  });

  it('omits footer when not provided', () => {
    const { queryByTestId } = render(
      <Modal visible title="x" onClose={jest.fn()} testID={TEST_ID}>
        <Text>body</Text>
      </Modal>
    );

    expect(queryByTestId('modal-footer')).toBeNull();
  });

  it('calls onClose when onRequestClose fires (Android back button)', () => {
    const onClose = jest.fn();
    const { UNSAFE_getByType } = render(
      <Modal visible title="x" onClose={onClose} testID={TEST_ID}>
        <Text>body</Text>
      </Modal>
    );

    const rnModal = UNSAFE_getByType(RNModal);
    rnModal.props.onRequestClose();
    expect(onClose).toHaveBeenCalledTimes(1);
  });

  it('applies slide layout by default (backdrop ends at bottom)', () => {
    const { getByTestId } = render(
      <Modal visible title="x" onClose={jest.fn()} testID={TEST_ID}>
        <Text>body</Text>
      </Modal>
    );

    const backdrop = StyleSheet.flatten(getByTestId(`${TEST_ID}-backdrop`).props.style) ?? {};
    expect(backdrop.justifyContent).toBe('flex-end');
  });

  it('applies fade layout when animationType="fade" (backdrop centered)', () => {
    const { getByTestId } = render(
      <Modal visible title="x" onClose={jest.fn()} animationType="fade" testID={TEST_ID}>
        <Text>body</Text>
      </Modal>
    );

    const backdrop = StyleSheet.flatten(getByTestId(`${TEST_ID}-backdrop`).props.style) ?? {};
    expect(backdrop.justifyContent).toBe('center');
    expect(backdrop.alignItems).toBe('center');
  });
});
